/**
 * Matomo Event-Tracking (Ziele/Conversions, z. B. für SEA-Kampagnen)
 *
 * Matomo kann klassisch (_paq) oder über den Tag Manager (Container aus Flowpack.Neos.Matomo) eingebunden
 * sein. Jedes Event wird daher mehrfach gemeldet – beides ist ohne Matomo wirkungslos, aber harmlos:
 *   1. window._mtm (Data-Layer des Tag Managers): { event: 'oib.<kategorie>', category, action, name }
 *      → im Tag Manager einen Trigger "Benutzerdefiniertes Ereignis" auf z. B. 'oib.kontakt'
 *        anlegen und damit ein Matomo-Event-Tag (Kategorie/Aktion/Name aus den Data-Layer-Variablen)
 *        oder ein Conversion-Tag (Google Ads o. Ä.) auslösen.
 *   2. window._paq (klassischer Tracker), falls der Tracking-Code direkt eingebunden ist.
 *   3. window.dataLayer (Google Tag Manager), nur wenn GTM konfiguriert ist (consent-inline.js legt
 *      den dataLayer an): gleiches Event-Objekt wie bei _mtm. GTM selbst lädt erst nach Einwilligung
 *      und verarbeitet dann auch zuvor auf der Seite gepushte Events.
 *
 * Event-Schema (Ziele in Matomo matchen auf die Ereignis-Aktion):
 *   Kategorie 'Kontakt'  → Aktionen 'Anruf', 'E-Mail', 'Route' (Google-Maps-Link), 'Terminbuchung' (Doctolib)
 *   Kategorie 'KI-Agent' → Nutzung durch KI-Agenten im Browser (WebMCP, siehe webmcp.js):
 *     'WebMCP verfügbar'          Name: Registrierungs-API ('registerTool' / 'provideContext')
 *     'Tool-Aufruf' / 'Tool-Fehler'  Name: WebMCP-Tool (z. B. 'search_site')
 *
 * Abrufe ohne Browser (llms.txt, Markdown-Seiten, ARD-Manifest, sitemap.xml durch Crawler) erfasst
 * stattdessen der Server, siehe Classes/Service/AgentTrackingService.php.
 */

export function trackEvent(category, action, name, value) {
	window._paq = window._paq || []
	const event = ['trackEvent', category, action]
	if (name !== undefined && name !== null && name !== '') {
		event.push(String(name))
		if (typeof value === 'number' && !Number.isNaN(value)) {
			event.push(value)
		}
	}
	window._paq.push(event)

	const dataLayerEvent = {
		event: 'oib.' + String(category).toLowerCase().replace(/[^a-z0-9]+/g, '-'),
		category,
		action,
		name: name !== undefined && name !== null ? String(name) : '',
	}

	window._mtm = window._mtm || []
	window._mtm.push({ ...dataLayerEvent })

	if (Array.isArray(window.dataLayer)) {
		window.dataLayer.push({ ...dataLayerEvent })
	}
}

/**
 * Klicks auf Telefon-, E-Mail- und Google-Maps-Links global tracken.
 * Capture-Phase, damit auch Links in Vue-Komponenten und Elemente mit stopPropagation() erfasst werden.
 */
export function initClickTracking() {
	document.addEventListener(
		'click',
		(e) => {
			const link = e.target.closest ? e.target.closest('a[href]') : null
			if (!link) return

			const href = link.getAttribute('href') || ''
			if (href.startsWith('tel:')) {
				trackEvent('Kontakt', 'Anruf', href.replace('tel:', '').trim())
			} else if (href.startsWith('mailto:')) {
				trackEvent('Kontakt', 'E-Mail', href.replace('mailto:', '').split('?')[0].trim())
			} else if (/^https?:\/\/(www\.)?(google\.[a-z.]+\/maps|maps\.app\.goo\.gl|goo\.gl\/maps)/i.test(href)) {
				trackEvent('Kontakt', 'Route', window.location.pathname)
			} else if (/^https?:\/\/(www\.)?doctolib\.[a-z]+\//i.test(href)) {
				trackEvent('Kontakt', 'Terminbuchung', window.location.pathname)
			}
		},
		true
	)
}
