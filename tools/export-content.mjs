#!/usr/bin/env node
/**
 * Freeze all content for the importer.
 *
 * PHP does not parse JavaScript, so the static build's content modules are
 * exported to JSON here and committed. The export carries a hash of every
 * source it reads, and `npm run check` fails when one no longer matches — which
 * is what stops someone editing site-data.mjs, forgetting to re-export, and
 * importing last week's copy without noticing.
 *
 * The check is in tools/check-sources.mjs, not the importer. This comment used
 * to say the importer enforced it and nothing did: reference/static is a
 * submodule at the repository root and is not mapped into the WordPress
 * container, so PHP cannot hash the files these digests describe.
 *
 * Three groups of content, all derived rather than retyped:
 *
 * 1. The entity arrays in content/site-data.mjs (disciplines and industries
 *    become terms with term meta, projects become case studies, jobs an option).
 * 2. The image manifest, which is the SOLE source of alt text for every
 *    photograph on the site.
 * 3. Content that only looks hardcoded — the management roster, company facts
 *    and certifications inside render.mjs, and the contact form's interests and
 *    recipient. Extracting these is the substance of "full editability": they
 *    are the fields an editor could not otherwise touch.
 */
import { writeFileSync, readFileSync } from 'node:fs';
import { createHash } from 'node:crypto';
import path from 'node:path';
import { STATIC_ROOT, REPO_ROOT } from './routes.mjs';

const OUT = path.join(REPO_ROOT, 'client-mu-plugins', 'emposo-core', 'data', 'site-export.json');

const read = (rel) => readFileSync(path.join(STATIC_ROOT, rel), 'utf8');
const hash = (rel) => createHash('sha256').update(readFileSync(path.join(STATIC_ROOT, rel))).digest('hex').slice(0, 16);

/**
 * Strip tags and collapse whitespace, preserving the text exactly otherwise.
 *
 * Tags are stripped until nothing changes (a single pass leaves `<scr<b>ipt>`
 * as `<script>`), and `&amp;` is decoded LAST so `&amp;nbsp;` stays the text
 * `&nbsp;` instead of being decoded twice.
 */
const text = (html) => {
	let out = html;
	for (let previous = null; previous !== out; ) {
		previous = out;
		out = out.replace(/<[^>]*>/g, '');
	}
	return out
		.replace(/&nbsp;/g, ' ')
		.replace(/&amp;/g, '&')
		.replace(/\s+/g, ' ')
		.trim();
};

// --------------------------------------------------------------------------
// 1. Entities
// --------------------------------------------------------------------------
const { disciplines, industries, projects, jobs } = await import(
	path.join(STATIC_ROOT, 'content', 'site-data.mjs')
);

// The English overlay (text fields by slug) and the i18n tables: UI strings,
// the English route map and the English case-study slugs (reference/static
// docs/i18n.md). PHP gets them as data, so its t() and localizePath() are
// table lookups with no logic to keep in sync with assemble.mjs.
const en = await import(path.join(STATIC_ROOT, 'content', 'site-data.en.mjs'));
const { STRINGS, PATHS, CASE_SLUGS } = await import(path.join(STATIC_ROOT, 'content', 'i18n.mjs'));
/** A dictionary entry, exactly as the reference's t() returns it. */
const tr = (locale, key) => {
	const value = STRINGS[locale]?.[key] ?? STRINGS.de[key];
	if (value === undefined) throw new Error(`i18n key missing in the reference: ${key}`);
	return value;
};
/** A German entity's English text fields (the overlay), keyed by slug. */
const english = (kind, entity, fields) => Object.fromEntries(fields.filter((f) => en[kind]?.[entity.slug]?.[f] !== undefined).map((f) => [f, en[kind][entity.slug][f]]));

/**
 * A `const name = [...]` literal from render.mjs, evaluated.
 *
 * render.mjs keeps the certifications, the company facts and the management
 * roster as module-private constants, so they cannot be imported. They are
 * plain data literals in the pinned reference, so evaluating the literal is
 * exact where a regex over the rendered markup would not be.
 */
function renderConstant(name) {
	const source = read('content/render.mjs');
	const start = source.search(new RegExp(`const ${name}\\s*=\\s*\\[`));
	if (start === -1) throw new Error(`render.mjs has no const ${name}`);
	let depth = 0;
	let i = source.indexOf('[', start);
	const from = i;
	for (; i < source.length; i += 1) {
		if (source[i] === '[') depth += 1;
		if (source[i] === ']') depth -= 1;
		if (depth === 0) break;
	}
	return new Function(`return ${source.slice(from, i + 1)};`)();
}

/*
 * Industry terms: the filter tokens, in the filter bar's source order
 * (aerospace, energy, health, industrial, automotive, technology). The five
 * with a tile carry its image and subtitle; `automotive` is a filter value
 * only, a child of `industrial`, spliced in right after it.
 */
const industryTerms = industries.map((industry) => ({
	slug: industry.filter.split(/\s+/)[0],
	name: industry.name,
	parent: null,
	meta: { tile: true, subtitle: industry.subtitle ?? '', image: industry.image, name_en: en.industries?.[industry.slug]?.name ?? industry.name, subtitle_en: en.industries?.[industry.slug]?.subtitle ?? '' },
}));
const industrialIndex = industryTerms.findIndex((term) => term.slug === 'industrial');
if (industrialIndex === -1) throw new Error('the industrial term is missing');
industryTerms.splice(industrialIndex + 1, 0, { slug: 'automotive', name: 'Automotive', parent: 'industrial', meta: { tile: false, subtitle: '', image: '', name_en: 'Automotive', subtitle_en: '' } });

const knownIndustry = new Set(industryTerms.map((term) => term.slug));
for (const project of projects) {
	for (const token of project.filter.split(/\s+/)) {
		if (!knownIndustry.has(token)) throw new Error(`${project.slug}: unknown industry token "${token}"`);
	}
}

/** Discipline groups are parent terms; the eight disciplines carry the table's copy. */
const groups = [...new Set(disciplines.map((d) => d.group))];
const disciplineTerms = [
	...groups.map((group) => ({ slug: group.toLowerCase(), name: group, parent: null, meta: {} })),
	...disciplines.map((d) => ({ slug: d.slug, name: d.name, parent: d.group.toLowerCase(), meta: { icon: d.icon, topics: d.topics, promise: d.promise, name_en: en.disciplines?.[d.slug]?.name ?? d.name, topics_en: en.disciplines?.[d.slug]?.topics ?? d.topics, promise_en: en.disciplines?.[d.slug]?.promise ?? d.promise } })),
];

/*
 * Outcomes are space-separated tokens ('optimize verzahnen'). No renderer shows
 * them any more; they stay classification data, declared so a new token fails
 * the export instead of disappearing.
 */
const OUTCOME_ORDER = [
	['optimize', 'Optimieren'],
	['transform', 'Transformieren'],
	['scale', 'Skalieren'],
	['verzahnen', 'Verzahnen'],
];
const usedOutcomes = new Set(projects.flatMap((p) => p.outcome.split(/\s+/)));
const declaredOutcomes = new Set(OUTCOME_ORDER.map(([slug]) => slug));
for (const slug of usedOutcomes) {
	if (!declaredOutcomes.has(slug)) {
		throw new Error(`Project data uses outcome "${slug}", which OUTCOME_ORDER does not declare`);
	}
}
const outcomeTerms = OUTCOME_ORDER.filter(([slug]) => usedOutcomes.has(slug)).map(([slug, name]) => ({ slug, name, parent: null, meta: {} }));

// --------------------------------------------------------------------------
// 2. Media
// --------------------------------------------------------------------------
const manifest = JSON.parse(read('assets/supplied/manifest.json'));
const assets = Object.entries(manifest).map(([key, asset]) => ({
	key,
	alt: asset.alt,
	alt_en: asset.alt_en ?? asset.alt,
	source: asset.source,
	src: asset.src,
	width: asset.width,
	height: asset.height,
	variants: asset.variants,
}));

// --------------------------------------------------------------------------
// 3. Content that only looks hardcoded
// --------------------------------------------------------------------------

/** Management roster, in the workbook's row order. */
function extractPeople() {
	return renderConstant('profiles').map((person) => ({
		name: person.name,
		roles: person.roles,
		bio: person.bio,
		// The English overlay is keyed by the portrait's image key, not the name.
		roles_en: en.people?.[person.image]?.roles ?? person.roles,
		bio_en: en.people?.[person.image]?.bio ?? person.bio,
		linkedin: person.link?.href ?? '',
		image: person.image,
	}));
}

/**
 * Contact-form interest options.
 *
 * The real options carry NO value attribute, so a browser submits the text:
 * ?interesse= deep links carry the German labels, and value === label here.
 */
function extractInterests(locale = 'de') {
	const source = read('partials/contact-form.html');
	const select = /<select[^>]*name="interest"[^>]*>([\s\S]*?)<\/select>/.exec(source)?.[1] ?? '';

	// Since the i18n layer the options are dictionary tokens ({{t:form.opt.x}}).
	return [...select.matchAll(/<option([^>]*)>([^<]*)<\/option>/g)]
		.map((m) => ({ attrs: m[1], label: text(m[2].replace(/\{\{t:([a-zA-Z0-9.]+)\}\}/g, (_, key) => tr(locale, key))) }))
		.filter(({ attrs }) => !/\bdisabled\b/.test(attrs))
		.map(({ attrs, label }) => ({ value: /value="([^"]*)"/.exec(attrs)?.[1] ?? label, label }))
		.filter(({ value }) => value !== '');
}

/** Contact recipient, read from the form action rather than retyped. */
function extractContactRecipient() {
	return /action="mailto:([^"?]+)/.exec(read('partials/contact-form.html'))?.[1] ?? '';
}

// --------------------------------------------------------------------------
const exportData = {
	schema: 2,
	note: 'Generated by tools/export-content.mjs. Do not hand-edit; re-run the exporter.',
	sources: {
		'content/site-data.mjs': hash('content/site-data.mjs'),
		'content/render.mjs': hash('content/render.mjs'),
		'assets/supplied/manifest.json': hash('assets/supplied/manifest.json'),
		'partials/contact-form.html': hash('partials/contact-form.html'),
		'content/site-data.en.mjs': hash('content/site-data.en.mjs'),
		'content/i18n.mjs': hash('content/i18n.mjs'),
	},
	terms: {
		emposo_discipline: disciplineTerms,
		emposo_industry: industryTerms,
		emposo_outcome: outcomeTerms,
	},
	projects,
	// English case studies: the German record's structure with the overlay's
	// text fields, under its English slug.
	projectsEn: projects.map((p) => ({ ...p, ...english('projects', p, ['name', 'headline', 'industry', 'metric', 'label', 'challenge', 'solution', 'results', 'facts']), slug: CASE_SLUGS[p.slug] ?? p.slug, translationOf: p.slug })),
	assets,
	people: extractPeople(),
	options: {
		// The facts constant holds dictionary keys since the i18n layer.
		facts: renderConstant('companyFactData').map((f) => ({ icon: f.icon, value: tr('de', f.value), label: tr('de', f.label) })),
		facts_en: renderConstant('companyFactData').map((f) => ({ icon: f.icon, value: tr('en', f.value), label: tr('en', f.label) })),
		certifications: renderConstant('certifications'),
		jobs,
		jobs_en: jobs.map((job) => ({ ...job, ...(en.jobs?.[job.slug] ?? {}) })),
		interests: extractInterests('de'),
		interests_en: extractInterests('en'),
		contactRecipient: extractContactRecipient(),
	},
};

writeFileSync(OUT, `${JSON.stringify(exportData, null, '\t')}\n`);

// The i18n tables for PHP: UI strings per locale, the route map and the
// English case-study slugs.
const I18N_OUT = path.join(REPO_ROOT, 'client-mu-plugins', 'emposo-core', 'data', 'i18n.json');
writeFileSync(I18N_OUT, `${JSON.stringify({ schema: 1, note: 'Generated by tools/export-content.mjs from reference/static content/i18n.mjs. Do not hand-edit.', sources: { 'content/i18n.mjs': hash('content/i18n.mjs') }, strings: STRINGS, paths: PATHS, caseSlugs: CASE_SLUGS }, null, '\t')}\n`);

console.log(`wrote ${path.relative(REPO_ROOT, OUT)}`);
console.log(`  terms:          ${disciplineTerms.length} discipline, ${industryTerms.length} industry, ${outcomeTerms.length} outcome`);
console.log(`  projects:       ${projects.length}`);
console.log(`  assets:         ${assets.length}`);
console.log(`  people:         ${exportData.people.length}`);
console.log(`  facts:          ${exportData.options.facts.length}`);
console.log(`  certifications: ${exportData.options.certifications.join(', ')}`);
console.log(`  jobs:           ${jobs.length}`);
console.log(`  interests:      ${exportData.options.interests.length}`);
console.log(`  recipient:      ${exportData.options.contactRecipient || '(none found)'}`);
