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
	meta: { tile: true, subtitle: industry.subtitle ?? '', image: industry.image },
}));
const industrialIndex = industryTerms.findIndex((term) => term.slug === 'industrial');
if (industrialIndex === -1) throw new Error('the industrial term is missing');
industryTerms.splice(industrialIndex + 1, 0, { slug: 'automotive', name: 'Automotive', parent: 'industrial', meta: { tile: false, subtitle: '', image: '' } });

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
	...disciplines.map((d) => ({ slug: d.slug, name: d.name, parent: d.group.toLowerCase(), meta: { icon: d.icon, topics: d.topics, promise: d.promise } })),
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
function extractInterests() {
	const source = read('partials/contact-form.html');
	const select = /<select[^>]*name="interest"[^>]*>([\s\S]*?)<\/select>/.exec(source)?.[1] ?? '';

	return [...select.matchAll(/<option([^>]*)>([^<]*)<\/option>/g)]
		.map((m) => ({ attrs: m[1], label: text(m[2]) }))
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
	},
	terms: {
		emposo_discipline: disciplineTerms,
		emposo_industry: industryTerms,
		emposo_outcome: outcomeTerms,
	},
	projects,
	assets,
	people: extractPeople(),
	options: {
		facts: renderConstant('companyFactData'),
		certifications: renderConstant('certifications'),
		jobs,
		interests: extractInterests(),
		contactRecipient: extractContactRecipient(),
	},
};

writeFileSync(OUT, `${JSON.stringify(exportData, null, '\t')}\n`);

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
