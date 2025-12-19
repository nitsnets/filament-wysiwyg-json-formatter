# Filament WYSIWYG JSON Formatter

A Filament 4 form component that provides a WYSIWYG editor with structured JSON output and user mentions support.

## Features

- ✅ Full WYSIWYG editor with contenteditable
- ✅ Bidirectional HTML ↔ structured JSON conversion
- ✅ User mentions with search panel
- ✅ Customizable toolbar with buttons: bold, italic, underline, strike, code, codeBlock, link, lists, checklist, headers, blockquote, textColor
- ✅ Dark mode support
- ✅ Optimized styles with PostCSS
- ✅ Compatible with Livewire/Alpine.js
- ✅ Structured and easy-to-process data format

## Installation

Install the package via Composer:

```bash
composer require nitsnets/filament-wysiwyg-json-formatter
```

### Package Development

If you're contributing to the package development, you need to install npm dependencies and compile assets:

```bash
npm install
npm run build
```

## Basic Usage

```php
use Nitsnets\FilamentWysiwygJsonFormatter\Forms\Components\WysiwygEditor;

WysiwygEditor::make('content')
    ->label('Content')
    ->required()
```

## Customize Toolbar Buttons

You can customize the buttons that appear in the toolbar:

```php
WysiwygEditor::make('content')
    ->toolbarButtons([
        'bold',
        'italic',
        'underline',
        'strike',
        'code',
        'codeBlock',
        'link',
        'unorderedList',
        'orderedList',
        'checklist',
        'divider',
        'h1',
        'h2',
        'h3',
        'blockquote',
        'textColor',
    ])
```

### Available Buttons

- **Text formatting**: `bold`, `italic`, `underline`, `strike`, `code`
- **Blocks**: `codeBlock`, `h1`, `h2`, `h3`, `blockquote`
- **Lists**: `unorderedList`, `orderedList`, `checklist`
- **Others**: `link`, `divider`, `textColor`

## User Mentions

Enable user mentions by passing an array of users:

```php
use App\Models\User;

WysiwygEditor::make('content')
    ->mentions(
        User::all()->map(fn (User $user) => [
            'id' => (string) $user->id,
            'label' => $user->name,
        ])->toArray()
    )
```

Users can mention others by typing `@` followed by the user's name. Mentions are saved in structured JSON format:

```json
{
  "type": "tag",
  "user": {
    "id": 123,
    "username": "John Doe",
    "name": "John Doe"
  }
}
```

## Data Format

The component saves and reads data in structured JSON format:

```json
{
  "comment": [
    {
      "text": "Hello ",
      "attributes": {
        "bold": true
      }
    },
    {
      "type": "tag",
      "user": {
        "id": 123,
        "username": "John",
        "name": "John"
      }
    },
    {
      "text": "\n",
      "attributes": {}
    }
  ]
}
```

### Supported Element Types

- **Formatted text**: bold, italic, underline, strike, code
- **Links**: with `link` attribute
- **Text colors**: with `color-class` attribute
- **Lists**: bullet, ordered, checklist (checked/unchecked)
- **Blocks**: headers (h1-h3), blockquote, code-block
- **Mentions**: `tag` type with user information
- **Separators**: `divider` type

## Complete Example

```php
use Nitsnets\FilamentWysiwygJsonFormatter\Forms\Components\WysiwygEditor;
use App\Models\User;

WysiwygEditor::make('comment_content')
    ->label('Comment')
    ->placeholder('Write a comment or press «@» to mention users')
    ->required()
    ->toolbarButtons([
        'bold',
        'italic',
        'underline',
        'link',
        'unorderedList',
        'orderedList',
        'checklist',
        'h2',
        'h3',
        'blockquote',
    ])
    ->mentions(
        User::query()
            ->get()
            ->map(fn (User $user) => [
                'id' => (string) $user->id,
                'label' => $user->name ?? $user->email,
            ])
            ->toArray()
    )
    ->columnSpanFull()
```

## Validation

The component is compatible with all Filament validation rules:

```php
WysiwygEditor::make('content')
    ->required()
    ->minLength(10)
    ->maxLength(5000)
```

## Translations

The plugin includes translations in multiple European languages by default.

### Available Languages

- 🇪🇸 Spanish (`es`)
- 🇬🇧 English (`en`)
- 🇩🇪 German (`de`)
- 🇫🇷 French (`fr`)
- 🇮🇹 Italian (`it`)
- 🇵🇹 Portuguese (`pt`)
- 🇵🇱 Polish (`pl`)
- 🇳🇱 Dutch (`nl`)
- 🇷🇺 Russian (`ru`)
- 🇪🇸 Catalan (`ca`)

### Publishing Translations

If you want to customize translations, you can publish the language files:

```bash
php artisan vendor:publish --tag=filament-wysiwyg-json-formatter-translations
```

This will copy the translation files to `lang/vendor/filament-wysiwyg-json-formatter/` where you can modify them.

### Adding New Languages

To add support for a new language:

1. Create a new directory in `lang/vendor/filament-wysiwyg-json-formatter/` with the language code (e.g., `fr`, `de`, `pt`)
2. Copy the content from `es/wysiwyg.php` or `en/wysiwyg.php`
3. Translate the texts to the new language

### Translation Structure

```php
return [
    'toolbar' => [
        'bold' => 'Bold',
        'italic' => 'Italic',
        // ... more buttons
    ],
    'mentions' => [
        'title' => 'Mention users',
        'no_users_found' => 'No users found',
        'close' => 'Close',
    ],
    'json_preview' => [
        'title' => 'JSON Preview',
        'close' => 'Close',
    ],
    'prompts' => [
        'enter_url' => 'Enter URL:',
    ],
];
```

## Styles

Styles are loaded asynchronously only when the component is used (`loadedOnRequest`), which optimizes performance:

- Full dark mode support
- Optimized CSS with PostCSS and cssnano
- Responsive design
- Smooth animations and transitions

## Compatibility

- **PHP**: ^8.2
- **Filament**: ^4.0
- **Laravel**: ^11.0

### Dependencies

The package requires the following dependencies which are automatically installed:

- `mallardduck/blade-lucide-icons` - For Lucide icons used in the toolbar

## License

MIT

## Credits

Developed by [Nitsnets](https://nitsnets.com)
