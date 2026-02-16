export default async function initMirador(miradorEl) {
    const manifestUrl = miradorEl.getAttribute("data-manifestId");

    if (!manifestUrl) {
        return;
    }

    const exists = await manifestExists(manifestUrl);

    if (!exists) {
        console.warn("IIIF manifest not found:", manifestUrl);
        miradorEl.style.display = "none";
        return;
    }

    const [{ default: Mirador }, { default: miradorDownloadPlugins }] = await Promise.all([
        import( 'mirador'),
        import('mirador-dl-plugin'),
    ]);

    const config = {
        id: 'mirador',
        miradorDownloadPlugin: {
            restrictDownloadOnSizeDefinition: true,
        },
        windows: [{
            loadedManifest: miradorEl.getAttribute("data-manifestId"),
            thumbnailNavigationPosition: 'far-right',
        }],
        window: {
            allowClose: false,
            allowMaximize: false,
            allowFullscreen: true,
            allowTopMenuButton: true,
            defaultSideBarPanel: 'info',
            sideBarOpenByDefault: false,
            views: [{ key: 'single' }, { key: 'gallery' }],
        },
        workspace: {
            showZoomControls: true,
            type: 'mosaic',
        },
        workspaceControlPanel: {
            enabled: false,
        },
    };

    Mirador.viewer(config, [...miradorDownloadPlugins]);
}

async function manifestExists(url) {
    try {
        const res = await fetch(url, { method: "HEAD" });

        if (res.status === 405) {
            const fallback = await fetch(url, { method: "GET" });
            return fallback.ok;
        }

        return res.ok;
    } catch {
        return false;
    }
}
