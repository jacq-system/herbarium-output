export default async function initMirador(miradorEl) {
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
