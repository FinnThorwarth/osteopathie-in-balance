/**
 * WebMCP: stellt KI-Agenten im Browser (Chrome, navigator.modelContext) lesende Tools bereit –
 * Seiteninhalt als Markdown und Kontaktdaten (eine Volltextsuche fehlt, solange /api/search ohne
 * Flowpack.SimpleSearch keine Treffer liefert). Ohne WebMCP-fähigen Browser
 * (bzw. ohne Origin-Trial-Token, Thorwarth.BaseSite.agentic.webmcpOriginTrialToken) passiert nichts.
 *
 * Alle Tools sind read-only (annotations.readOnlyHint). Jeder Tool-Aufruf wird als Matomo-Event
 * erfasst (Kategorie 'KI-Agent', siehe tracking.js).
 */

import { trackEvent } from './tracking.js'

function siteConfig() {
	try {
		const raw = document.getElementById('site-config')?.textContent
		return raw ? JSON.parse(raw) : {}
	} catch (e) {
		return {}
	}
}

function textResult(value) {
	return { content: [{ type: 'text', text: typeof value === 'string' ? value : JSON.stringify(value) }] }
}

/**
 * Nur Pfade dieser Website zulassen (kein Abruf fremder Domains über die Tools)
 */
function sameOriginUrl(input) {
	const url = new URL(String(input || '/'), window.location.origin)
	if (url.origin !== window.location.origin) throw new Error('Nur Seiten dieser Website sind erlaubt')
	return url
}

function markdownUrlFor(input) {
	const url = sameOriginUrl(input)
	const path = url.pathname.replace(/\/+$/, '')
	url.pathname = path === '' ? '/index.md' : path.endsWith('.md') ? path : `${path}.md`
	url.search = ''
	url.hash = ''
	return url
}

function withTracking(tool) {
	const execute = tool.execute
	return {
		...tool,
		async execute(input) {
			trackEvent('KI-Agent', 'Tool-Aufruf', tool.name)
			try {
				return await execute(input)
			} catch (e) {
				trackEvent('KI-Agent', 'Tool-Fehler', tool.name)
				throw e
			}
		},
	}
}

export function buildTools(config = siteConfig()) {
	return [
		{
			name: 'get_page_markdown',
			description: 'Liefert den Inhalt einer Seite dieser Website als Markdown (ohne Navigation und Layout). Ohne Angabe: die aktuell geöffnete Seite.',
			inputSchema: {
				type: 'object',
				properties: {
					path: { type: 'string', description: 'Pfad oder URL einer Seite dieser Website, z. B. "/fuer-saeuglinge/bauchweh"' },
				},
			},
			annotations: { readOnlyHint: true },
			async execute(input = {}) {
				const url = markdownUrlFor(input.path || window.location.pathname)
				const response = await fetch(url.href, { headers: { Accept: 'text/markdown' } })
				if (!response.ok) throw new Error(`HTTP ${response.status}`)
				return textResult(await response.text())
			},
		},
		{
			name: 'get_contact_details',
			description: 'Liefert Praxis, Standorte mit Öffnungszeiten, Telefon, E-Mail und den Link zur Online-Terminbuchung (Doctolib).',
			inputSchema: { type: 'object', properties: {} },
			annotations: { readOnlyHint: true },
			async execute() {
				return textResult({
					siteName: config.siteName,
					company: config.company,
					bookingUrl: config.bookingUrl,
					overviewUrl: new URL('/llms.txt', window.location.origin).href,
				})
			},
		},
	].map(withTracking)
}

export function registerWebMcpTools() {
	const context = navigator.modelContext || document.modelContext
	if (!context) return false

	const tools = buildTools()
	if (typeof context.registerTool === 'function') {
		tools.forEach((tool) => {
			try { context.registerTool(tool) } catch (e) { /* bereits registriert o. ä. */ }
		})
		trackEvent('KI-Agent', 'WebMCP verfügbar', 'registerTool')
		return true
	}
	if (typeof context.provideContext === 'function') {
		context.provideContext({ tools })
		trackEvent('KI-Agent', 'WebMCP verfügbar', 'provideContext')
		return true
	}
	return false
}
