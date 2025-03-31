# KrbeFileManager

[English](docs/en/README.md) | [Français](docs/fr/README.md)

Bundle Symfony pour la gestion de fichiers. Ce bundle fournit un widget modal intuitif pour la gestion des fichiers dans vos applications Symfony.

> **Note importante** : Bien que le bundle soit conçu pour supporter AWS S3, la version actuelle ne fonctionne qu'avec le stockage local. La compatibilité AWS S3 sera ajoutée dans une future version.

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

## Exemples d'utilisation

### Dans un formulaire Symfony

```php
// src/Form/ArticleType.php
public function buildForm(FormBuilderInterface $builder, array $options)
{
    $builder
        ->add('image', TextType::class, [
            'attr' => [
                'data-krbe-filemanager' => 'img'
            ]
        ]);
}
```

### Dans un template Twig

```twig
{# templates/article/new.html.twig #}
{{ form_start(form) }}
    {{ form_row(form.image) }}
    <button type="submit">Enregistrer</button>
{{ form_end(form) }}
```

### Avec JavaScript

```javascript
// Écoutez les événements du FileManager
document.querySelector('input[data-krbe-filemanager]').addEventListener('krbeFileManager:selected', function(e) {
    console.log('Fichier sélectionné:', e.detail.selectedFilePath);
});
```

## FAQ

### Comment personnaliser le chemin de stockage des fichiers ?

Utilisez l'interface `UploadPathResolverInterface` pour définir votre propre logique de stockage. Voir la section "Les Resolvers" pour plus de détails.

### Comment gérer les quotas de stockage ?

Implémentez l'interface `QuotaResolverInterface` pour définir des limites de stockage personnalisées. Voir la section "Les Resolvers" pour plus de détails.

### Comment ajouter des types de fichiers personnalisés ?

Modifiez la configuration `allowed_mime_types` dans votre fichier `krbe_file_manager.yaml`.

## Prérequis

- PHP 8.1 ou supérieur
- Symfony 7.0 ou supérieur
- Composer

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