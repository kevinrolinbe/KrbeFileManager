# KrbeFileManager

[English](../en/README.md) | [Français](../fr/README.md)

A Symfony bundle for file management. This bundle provides an intuitive modal widget for managing files in your Symfony applications.

## About

As a passionate freelance developer ([kevinrolin.be](https://kevinrolin.be)), I created this module because I couldn't find a solution that combined all the features I needed: simple installation and usage, image management with crop and compression (especially in WebP), and an intuitive user interface.

In my quest for an optimal solution, I experimented with artificial intelligence to speed up development and ensure a clean, maintainable codebase. This module is the result of this exploration, combining classical development practices with the advantages of AI.

## Features

- 🖼️ Image management with integrated crop
- 📦 Automatic image compression
- 🌐 WebP format support
- 📱 Responsive and intuitive interface
- 🔒 Quota management per user/company
- 📁 Flexible file organization
- 🎨 Complete interface customization
- 🔄 Drag & drop support
- 📊 Image preview
- 🛠️ Simple and flexible configuration

### Upcoming Features

- 📝 TinyMCE integration
- ☁️ AWS S3 compatibility

## Dependencies

- PHP 8.1+
- Symfony 7.0+

## Installation

```bash
composer require krbe/file-manager-bundle
```

## Configuration

1. Add the bundle to `config/bundles.php`:

```php
return [
    // ...
    Krbe\FileManagerBundle\KrbeFileManagerBundle::class => ['all' => true],
];
```

2. Configure the bundle in `config/packages/krbe_file_manager.yaml`:

```yaml
krbe_file_manager:
    required_role: 'ROLE_FILEMANAGER' # empty to disable role checking
    quota_max: -1  # -1 for unlimited, otherwise in bytes
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

3. Configure services in `config/services.yaml`:

```yaml
services:
    # Resolver Configuration
    Krbe\FileManagerBundle\Resolver\UploadPathResolverInterface:
        class: App\Resolver\CompanyUploadPathResolver
        arguments:
            $security: '@security.helper'
            $projectDir: '%kernel.project_dir%'

    Krbe\FileManagerBundle\Resolver\QuotaResolverInterface:
        class: App\Resolver\UserQuotaResolver
```

4. Add environment variables to your `.env`:

```env
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_REGION=your_region
AWS_BUCKET=your_bucket
```

### The Resolvers

The bundle uses two Resolver interfaces to customize behavior:

#### UploadPathResolverInterface

This interface allows you to customize the file storage path. For example, to organize files by company:

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

This interface allows you to define custom quotas per user or company:

```php
namespace App\Resolver;

use Krbe\FileManagerBundle\Resolver\QuotaResolverInterface;

class UserQuotaResolver implements QuotaResolverInterface
{
    public function resolve(): int
    {
        // Returns quota in bytes
        return 100 * 1024 * 1024; // 100MB
    }
}
```

## Usage

### Modal Widget Integration

1. Add the widget to your Twig template:

```twig
{# templates/your_template.html.twig #}
{{ render(controller('Krbe\\FileManagerBundle\\Controller\\FileManagerController::widgetModal')) }}
```

2. Enable FileManager on your form fields:

```html
<!-- For all file types -->
<input type="text" data-krbe-filemanager>

<!-- For images only -->
<input type="text" data-krbe-filemanager="img">
```

### Direct FileManager Access

You can also access the FileManager directly through the following routes:

- `GET /file-manager`: Main FileManager interface
- `POST /api/files/upload`: File upload
- `GET /api/files/{filename}`: File download
- `DELETE /api/files/{filename}`: File deletion

### Configuration Customization

#### JavaScript Configuration

You can customize the appearance and behavior of the FileManager by configuring JavaScript options. There are two ways to do this:

1. **Before loading the script**:
```html
<script>
    window.KrbeFileManagerConfig = {
        inputText: 'Select a file',
        buttonSelectText: 'Browse',
        buttonResetText: 'Clear',
        // ... other options
    };
</script>
<script src="krbe-filemanager-picker.js"></script>
```

2. **After loading the script**:
```javascript
KrbeFileManager.setConfig({
    inputText: 'Select a file',
    buttonSelectText: 'Browse',
    buttonResetText: 'Clear',
    // ... other options
});
```

Available options are:
- `wrapperClass`: main container class (default: 'krbe-filemanager-wrapper')
- `inputAttribute`: attribute to identify inputs (default: 'data-krbe-filemanager')
- `inputText`: input placeholder text (default: 'Choose a file')
- `inputClass`: input class (default: 'krbe-filemanager-input')
- `buttonSelectText`: select button text (default: 'Choose a file')
- `buttonSelectClass`: select button class (default: 'krbe-filemanager-select-btn')
- `buttonResetText`: reset button text (default: 'Reset')
- `buttonResetClass`: reset button class (default: 'krbe-filemanager-reset-btn')
- `previewClass`: preview container class (default: 'krbe-filemanager-preview')

Configuration is non-destructive, only the properties you specify will be modified, others will keep their default values.

## License

MIT 