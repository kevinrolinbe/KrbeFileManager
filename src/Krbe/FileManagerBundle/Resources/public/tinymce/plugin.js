(function() {
    tinymce.PluginManager.add('krbefilemanager', function(editor, url) {
        // Récupération du chemin configuré ou utilisation de la valeur par défaut
        const fileManagerPath = editor.getParam('fileManagerPath', '/krbe/filemanager');
        
        editor.ui.registry.addButton('krbefilemanager', {
            text: 'File Manager',
            icon: 'image',
            tooltip: 'Insérer une image depuis le File Manager',
            onAction: function() {
                editor.windowManager.openUrl({
                    title: 'File Manager',
                    url: fileManagerPath + '/tinymce',
                    width: 900,
                    height: 600,
                    buttons: [
                        {
                            type: 'cancel',
                            text: 'Fermer'
                        }
                    ]
                });
            }
        });

        return {
            getMetadata: function() {
                return {
                    name: 'Krbe File Manager',
                    url: 'https://krbe.fr',
                    version: '1.0'
                };
            }
        };
    });
})(); 