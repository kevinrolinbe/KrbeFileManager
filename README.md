# KrbeFileManager

[English](../en/README.md) | [Français](../fr/README.md)

Bundle Symfony pour la gestion de fichiers. Ce bundle fournit un widget modal intuitif pour la gestion des fichiers dans vos applications Symfony.

## À propos

Développeur freelance passionné ([kevinrolin.be](https://kevinrolin.be)), j'ai créé ce module car je ne trouvais pas de solution qui réunissait toutes les fonctionnalités dont j'avais besoin : simplicité d'installation et d'utilisation, gestion des images avec crop et compression (notamment en WebP), et une interface utilisateur intuitive.

Dans ma quête d'une solution optimale, j'ai expérimenté l'utilisation de l'intelligence artificielle pour accélérer le développement et m'assurer d'une base de code propre et maintenable. Ce module est le fruit de cette exploration, combinant les pratiques de développement classiques avec les avantages de l'IA.

## Fonctionnalités

- 🖼️ Gestion des images avec crop intégré
- 📦 Compression automatique des images
- 🌐 Support du format WebP
- 📱 Interface responsive et intuitive
- 🔒 Gestion des quotas par utilisateur/entreprise
- 📁 Organisation flexible des fichiers
- 🎨 Personnalisation complète de l'interface
- 🔄 Support du drag & drop
- 📊 Prévisualisation des images
- 🛠️ Configuration simple et flexible

### Fonctionnalités à venir

- 📝 Intégration avec TinyMCE
- ☁️ Compatibilité AWS S3

## Dépendances

- PHP 8.1+
- Symfony 7.0+

## Installation

```bash
composer require krbe/file-manager-bundle
```

## Configuration

1. Ajoutez le bundle dans `config/bundles.php` :

```php
return [
    // ...
    Krbe\FileManagerBundle\KrbeFileManagerBundle::class => ['all' => true],
];
```

2. Configurez le bundle dans `config/packages/krbe_file_manager.yaml` :

```yaml
krbe_file_manager:
    required_role: 'ROLE_FILEMANAGER' # vide pour ne pas vérifier de role
    quota_max: -1  # -1 pour illimité, sinon en octets
    max_file_size: 10485760  # 10MB
    allowed_mime_types:
        - image/jpeg
        - image/png
        - image/gif
        - image/webp
        - image/svg+xml
        - application/pdf
        - application/msword
        - application/vnd.openxmlformats-officedocument.wordprocessingml.document
        - application/vnd.ms-excel
        - application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
        - text/plain
        - text/csv
        - application/json
        - application/xml
        - text/xml
    image_processing:
        compression_enabled: true
        compression_quality: 80
        create_webp: true
        keep_original: true
    storage:
        type: local
        local:
            path: '%kernel.project_dir%/public/cdn'
        s3:
            key: '%env(AWS_ACCESS_KEY_ID)%'
            secret: '%env(AWS_SECRET_ACCESS_KEY)%'
            region: '%env(AWS_REGION)%'
            bucket: '%env(AWS_BUCKET)%'
            path: ''
```

3. Configurez les services dans `config/services.yaml` :

```yaml
services:
    # Configuration des Resolvers
    Krbe\FileManagerBundle\Resolver\UploadPathResolverInterface:
        class: App\Resolver\CompanyUploadPathResolver
        arguments:
            $security: '@security.helper'
            $projectDir: '%kernel.project_dir%'

    Krbe\FileManagerBundle\Resolver\QuotaResolverInterface:
        class: App\Resolver\UserQuotaResolver
```

4. Ajoutez les variables d'environnement dans votre `.env` :

```env
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_REGION=your_region
AWS_BUCKET=your_bucket
```

### Les Resolvers

Le bundle utilise deux interfaces de Resolver pour personnaliser le comportement :

#### UploadPathResolverInterface

Cette interface permet de personnaliser le chemin de stockage des fichiers. Par exemple, pour organiser les fichiers par entreprise :

```php
namespace App\Resolver;

use Krbe\FileManagerBundle\Resolver\UploadPathResolverInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CompanyUploadPathResolver implements UploadPathResolverInterface
{
    public function __construct(
        private Security $security,
        private string $projectDir
    ) {}

    public function resolve(): string
    {
        $user = $this->security->getUser();
        return sprintf('companies/%s', $user->getCompany()->getId());
    }
}
```

#### QuotaResolverInterface

Cette interface permet de définir des quotas personnalisés par utilisateur ou par entreprise :

```php
namespace App\Resolver;

use Krbe\FileManagerBundle\Resolver\QuotaResolverInterface;

class UserQuotaResolver implements QuotaResolverInterface
{
    public function resolve(): int
    {
        // Retourne le quota en octets
        return 100 * 1024 * 1024; // 100MB
    }
}
```

## Utilisation

### Intégration du Widget Modal

1. Ajoutez le widget dans votre template Twig :

```twig
{# templates/your_template.html.twig #}
{{ render(controller('Krbe\\FileManagerBundle\\Controller\\FileManagerController::widgetModal')) }}
```

2. Activez le FileManager sur vos champs de formulaire :

```html
<!-- Pour tous types de fichiers -->
<input type="text" data-krbe-filemanager>

<!-- Pour les images uniquement -->
<input type="text" data-krbe-filemanager="img">
```

### Accès Direct au FileManager

Vous pouvez également accéder directement au FileManager via les routes suivantes :

- `GET /file-manager` : Interface principale du FileManager
- `POST /api/files/upload` : Upload de fichiers
- `GET /api/files/{filename}` : Téléchargement de fichiers
- `DELETE /api/files/{filename}` : Suppression de fichiers

### Personnalisation de la Configuration

#### Configuration JavaScript

Vous pouvez personnaliser l'apparence et le comportement du FileManager en configurant les options JavaScript. Il y a deux façons de le faire :

1. **Avant le chargement du script** :
```html
<script>
    window.KrbeFileManagerConfig = {
        inputText: 'Sélectionner un fichier',
        buttonSelectText: 'Parcourir',
        buttonResetText: 'Effacer',
        // ... autres options
    };
</script>
<script src="krbe-filemanager-picker.js"></script>
```

2. **Après le chargement du script** :
```javascript
KrbeFileManager.setConfig({
    inputText: 'Sélectionner un fichier',
    buttonSelectText: 'Parcourir',
    buttonResetText: 'Effacer',
    // ... autres options
});
```

Les options disponibles sont :
- `wrapperClass` : classe du conteneur principal (défaut: 'krbe-filemanager-wrapper')
- `inputAttribute` : attribut pour identifier les inputs (défaut: 'data-krbe-filemanager')
- `inputText` : texte du placeholder de l'input (défaut: 'Choisir un fichier')
- `inputClass` : classe de l'input (défaut: 'krbe-filemanager-input')
- `buttonSelectText` : texte du bouton de sélection (défaut: 'Choisir un fichier')
- `buttonSelectClass` : classe du bouton de sélection (défaut: 'krbe-filemanager-select-btn')
- `buttonResetText` : texte du bouton de réinitialisation (défaut: 'Réinitialiser')
- `buttonResetClass` : classe du bouton de réinitialisation (défaut: 'krbe-filemanager-reset-btn')
- `previewClass` : classe du conteneur de prévisualisation (défaut: 'krbe-filemanager-preview')

La configuration se fait de manière non destructive, seules les propriétés que vous spécifiez seront modifiées, les autres conserveront leurs valeurs par défaut.

## Licence

MIT 