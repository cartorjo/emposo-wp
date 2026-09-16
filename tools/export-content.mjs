#!/usr/bin/env node
/**
 * Freeze all content for the importer.
 *
 * PHP does not parse JavaScript, so the static build's content modules are
 * exported to JSON here and committed. The export carries a hash of every
 * source it reads, and the importer refuses to run against a stale contract —
 * which is what stops someone editing site-data.mjs, forgetting to re-export,
 * and importing last week's copy without noticing.
 *
 * Three groups of content, all derived rather than retyped:
 *
 * 1. The three entity arrays in content/site-data.mjs.
 * 2. The image manifest, which is the SOLE source of alt text for every
 *    photograph on the site.
 * 3. Content that only looks hardcoded — the management roster and filter
 *    vocabulary inside render.mjs, and the FAQ, company facts, trust strip and
 *    locations inside the section and page markup. Extracting these is the
 *    substance of "full editability": they are the fields an editor cannot
 *    currently touch.
 */
import { writeFileSync, readFileSync } from 'node:fs';
import { createHash } from 'node:crypto';
import path from 'node:path';
import { STATIC_ROOT, REPO_ROOT } from './routes.mjs';

const OUT = path.join(REPO_ROOT, 'client-mu-plugins', 'emposo-core', 'data', 'site-export.json');

const read = (rel) => readFileSync(path.join(STATIC_ROOT, rel), 'utf8');
const hash = (rel) => createHash('sha256').update(readFileSync(path.join(STATIC_ROOT, rel))).digest('hex').slice(0, 16);

/** Strip tags and collapse whitespace, preserving the text exactly otherwise. */
const text = (html) =>
	html
		.replace(/<[^>]+>/g, '')
		.replace(/&amp;/g, '&')
		.replace(/&nbsp;/g, ' ')
		.replace(/\s+/g, ' ')
		.trim();

// --------------------------------------------------------------------------
// 1. Entities
// --------------------------------------------------------------------------
const { disciplines, industries, projects } = await import(
	path.join(STATIC_ROOT, 'content', 'site-data.mjs')
);

/**
 * Industry term slugs are the FILTER tokens, not the page slugs.
 *
 * This is the pivot the whole taxonomy design rests on: `filter: 'industrial
 * automotive'` means the term vocabulary is aerospace|energy|health|industrial|
 * automotive|technology, while the page routes stay aerospace-defense etc. One
 * structure then serves the filter buttons, the data-industry token string and
 * the project.industry display label.
 */
const industryTerms = industries.map((industry) => ({
	slug: industry.filter.split(/\s+/)[0],
	name: industry.name,
	parent: null,
	pageSlug: industry.slug,
}));

// `automotive` is a filter value with no page of its own, and it never appears
// without `industrial` — so it is a CHILD of the industrials term. That models
// the seven filter buttons over five industry pages without inventing a sixth.
const automotiveUsed = projects.some((p) => p.filter.split(/\s+/).includes('automotive'));
if (automotiveUsed) {
	const parent = industries.find((i) => i.filter.split(/\s+/)[0] === 'industrial');
	industryTerms.push({
		slug: 'automotive',
		name: 'Automotive',
		parent: parent ? parent.filter.split(/\s+/)[0] : null,
		pageSlug: null,
	});
}

/** Discipline groups become parent terms; the eight disciplines become children. */
const groups = [...new Set(disciplines.map((d) => d.group))];
const disciplineTerms = [
	...groups.map((group) => ({ slug: group.toLowerCase(), name: group, parent: null })),
	...disciplines.map((d) => ({ slug: d.slug, name: d.name, parent: d.group.toLowerCase() })),
];

const outcomeTerms = [...new Set(projects.map((p) => p.outcome))].map((slug) => ({
	slug,
	name: { optimize: 'Optimieren', transform: 'Transformieren', scale: 'Skalieren' }[slug] ?? slug,
	parent: null,
}));

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

/** Management roster, parsed out of render.mjs's management() template. */
function extractPeople() {
	const source = read('content/render.mjs');
	const people = [];

	// Full profiles: a figure with a name, a role and a bio.
	for (const m of source.matchAll(
		/<article class="management-profile">([\s\S]*?)<\/article>/g
	)) {
		const block = m[1];
		const name = text(/<h3[^>]*>([\s\S]*?)<\/h3>/.exec(block)?.[1] ?? '');
		const role = text(/class="management-profile__role"[^>]*>([\s\S]*?)<\/p>/.exec(block)?.[1] ?? '');
		const bio = [...block.matchAll(/<p(?![^>]*management-profile__role)[^>]*>([\s\S]*?)<\/p>/g)]
			.map((p) => text(p[1]))
			.filter((p) => p && p !== role);
		const linkedin = /href="(https:\/\/[^"]*linkedin[^"]*)"/.exec(block)?.[1] ?? '';
		const image = /\$\{picture\('([a-z0-9-]+)'/.exec(block)?.[1] ?? '';
		if (name) people.push({ name, role, bio, linkedin, image, initials: '' });
	}

	// Gallery entries: a local array of {name, image} or {name, initials}.
	const gallery = /const gallery\s*=\s*\[([\s\S]*?)\];/.exec(source)?.[1] ?? '';
	for (const m of gallery.matchAll(/\{([^}]*)\}/g)) {
		const entry = m[1];
		const name = /name:\s*'([^']*)'/.exec(entry)?.[1] ?? '';
		if (!name) continue;
		people.push({
			name,
			role: '',
			bio: [],
			linkedin: '',
			image: /image:\s*'([^']*)'/.exec(entry)?.[1] ?? '',
			initials: /initials:\s*'([^']*)'/.exec(entry)?.[1] ?? '',
		});
	}

	return people;
}

/** FAQ, from the native details/summary markup. */
function extractFaq() {
	const source = read('sections/07aa-faq.html');
	const items = [];
	for (const m of source.matchAll(/<details([^>]*)>([\s\S]*?)<\/details>/g)) {
		const [, attrs, body] = m;
		const summary = /<summary[^>]*>([\s\S]*?)<\/summary>/.exec(body)?.[1] ?? '';
		// The "+" affordance is a decorative aria-hidden span, not content.
		const question = text(summary.replace(/<span[^>]*aria-hidden="true"[^>]*>[\s\S]*?<\/span>/g, ''));
		const answer = text(body.replace(/<summary[\s\S]*?<\/summary>/, ''));
		if (question) items.push({ question, answer, open: /\bopen\b/.test(attrs) });
	}
	return items;
}

/** The four company numbers, with their icons and labels. */
function extractFacts() {
	const source = read('sections/03-models.html');
	const facts = [];
	const dl = /<dl class="company-facts">([\s\S]*?)<\/dl>/.exec(source)?.[1] ?? '';
	for (const m of dl.matchAll(/<div><dt>([\s\S]*?)<\/dt><dd>([\s\S]*?)<\/dd><\/div>/g)) {
		const [, dt, dd] = m;
		facts.push({
			icon: /\{\{icon:([a-z0-9-]+)\}\}/.exec(dt)?.[1] ?? '',
			// The value must survive byte-for-byte: '2.900+' carries a German
			// thousands separator the count-up script re-inserts, and the '+'
			// is part of the claim.
			value: text(/class="company-facts__value"[^>]*>([\s\S]*?)<\/span>/.exec(dt)?.[1] ?? ''),
			label: text(dd),
		});
	}
	return facts;
}

function extractTrustStrip() {
	const source = read('sections/03-models.html');
	const strip = /class="trust-strip"[^>]*>([\s\S]*?)<\/(?:ul|div|p)>/.exec(source)?.[1] ?? '';
	return [...strip.matchAll(/<(?:li|span|strong)[^>]*>([^<]+)<\/(?:li|span|strong)>/g)]
		.map((m) => text(m[1]))
		.filter(Boolean);
}

function extractLocations() {
	const source = read('pages/kontakt.html');

	// Scan forward from the list marker rather than bounding the match with a
	// closing </div>: the entries are themselves divs, so a non-greedy bound
	// stops at the first nested close and drops the last location.
	const start = source.indexOf('class="location-list"');
	if (start === -1) return [];
	const region = source.slice(start);

	return [...region.matchAll(/<div><strong>([^<]+)<\/strong><span>([^<]+)<\/span><\/div>/g)].map((m) => ({
		name: text(m[1]),
		label: text(m[2]),
	}));
}

/**
 * Contact-form interest options.
 *
 * The single source for the select, the server-side allowlist and every
 * generated ?interesse= link. Translating an option silently breaks inbound
 * links, so they are exported verbatim and keyed by value.
 */
function extractInterests() {
	const source = read('partials/contact-form.html');

	/*
	 * The real options carry NO value attribute — only the disabled placeholder
	 * has value="". A browser then submits the option's text, which is why
	 * ?interesse= deep links carry URL-encoded German labels and why renaming
	 * an option silently breaks every inbound link. So value === label here,
	 * deliberately, and both are exported verbatim.
	 */
	const select = /<select[^>]*name="interest"[^>]*>([\s\S]*?)<\/select>/.exec(source)?.[1] ?? '';

	return [...select.matchAll(/<option([^>]*)>([^<]*)<\/option>/g)]
		.map((m) => {
			const attrs = m[1];
			const label = text(m[2]);
			const explicit = /value="([^"]*)"/.exec(attrs)?.[1];
			return {
				value: explicit !== undefined ? explicit : label,
				label,
				placeholder: /\bdisabled\b/.test(attrs),
			};
		})
		.filter((option) => !option.placeholder && option.value !== '')
		.map(({ value, label }) => ({ value, label }));
}

/** Contact recipient, read from the form action rather than retyped. */
function extractContactRecipient() {
	const source = read('partials/contact-form.html');
	return /action="mailto:([^"?]+)/.exec(source)?.[1] ?? '';
}

// --------------------------------------------------------------------------
const exportData = {
	schema: 1,
	note: 'Generated by tools/export-content.mjs. Do not hand-edit; re-run the exporter.',
	sources: {
		'content/site-data.mjs': hash('content/site-data.mjs'),
		'content/render.mjs': hash('content/render.mjs'),
		'assets/supplied/manifest.json': hash('assets/supplied/manifest.json'),
		'sections/03-models.html': hash('sections/03-models.html'),
		'sections/07aa-faq.html': hash('sections/07aa-faq.html'),
		'partials/contact-form.html': hash('partials/contact-form.html'),
		'pages/kontakt.html': hash('pages/kontakt.html'),
	},
	terms: {
		emposo_discipline: disciplineTerms,
		emposo_industry: industryTerms,
		emposo_outcome: outcomeTerms,
	},
	disciplines,
	industries,
	projects,
	assets,
	people: extractPeople(),
	options: {
		faq: extractFaq(),
		facts: extractFacts(),
		trustStrip: extractTrustStrip(),
		locations: extractLocations(),
		interests: extractInterests(),
		contactRecipient: extractContactRecipient(),
	},
};

writeFileSync(OUT, `${JSON.stringify(exportData, null, '\t')}\n`);

console.log(`wrote ${path.relative(REPO_ROOT, OUT)}`);
console.log(`  terms:        ${disciplineTerms.length} discipline, ${industryTerms.length} industry, ${outcomeTerms.length} outcome`);
console.log(`  entities:     ${disciplines.length} disciplines, ${industries.length} industries, ${projects.length} projects`);
console.log(`  assets:       ${assets.length}`);
console.log(`  people:       ${exportData.people.length}`);
console.log(`  faq:          ${exportData.options.faq.length}`);
console.log(`  facts:        ${exportData.options.facts.length}`);
console.log(`  trust strip:  ${exportData.options.trustStrip.length}`);
console.log(`  locations:    ${exportData.options.locations.length}`);
console.log(`  interests:    ${exportData.options.interests.length}`);
console.log(`  recipient:    ${exportData.options.contactRecipient || '(none found)'}`);
