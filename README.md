# Filament WYSIWYG JSON Formatter

Un componente de formulario para Filament 4 que proporciona un editor WYSIWYG con salida en formato JSON estructurado y soporte para menciones de usuarios.

## Características

- ✅ Editor WYSIWYG completo con contenteditable
- ✅ Conversión bidireccional HTML ↔ JSON estructurado
- ✅ Menciones de usuarios con panel de búsqueda
- ✅ Toolbar personalizable con botones: bold, italic, underline, strike, code, codeBlock, link, lists, checklist, headers, blockquote, textColor
- ✅ Soporte para dark mode
- ✅ Estilos optimizados con PostCSS
- ✅ Compatible con Livewire/Alpine.js
- ✅ Formato de datos estructurado y fácil de procesar

## Instalación

Instala el paquete vía Composer:

```bash
composer require nitsnets/filament-wysiwyg-json-formatter
```

### Desarrollo del Paquete

Si estás contribuyendo al desarrollo del paquete, necesitas instalar las dependencias de npm y compilar los assets:

```bash
npm install
npm run build
```

## Uso Básico

```php
use Nitsnets\FilamentWysiwygJsonFormatter\Forms\Components\WysiwygEditor;

WysiwygEditor::make('content')
    ->label('Contenido')
    ->required()
```

## Personalizar Botones del Toolbar

Puedes personalizar los botones que aparecen en la barra de herramientas:

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

### Botones Disponibles

- **Formato de texto**: `bold`, `italic`, `underline`, `strike`, `code`
- **Bloques**: `codeBlock`, `h1`, `h2`, `h3`, `blockquote`
- **Listas**: `unorderedList`, `orderedList`, `checklist`
- **Otros**: `link`, `divider`, `textColor`

## Menciones de Usuarios

Habilita las menciones de usuarios pasando un array de usuarios:

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

Los usuarios pueden mencionar a otros escribiendo `@` seguido del nombre del usuario. Las menciones se guardan en formato JSON estructurado:

```json
{
  "type": "tag",
  "user": {
    "id": 123,
    "username": "Juan Pérez",
    "name": "Juan Pérez"
  }
}
```

## Formato de Datos

El componente guarda y lee datos en formato JSON estructurado:

```json
{
  "comment": [
    {
      "text": "Hola ",
      "attributes": {
        "bold": true
      }
    },
    {
      "type": "tag",
      "user": {
        "id": 123,
        "username": "Juan",
        "name": "Juan"
      }
    },
    {
      "text": "\n",
      "attributes": {}
    }
  ]
}
```

### Tipos de Elementos Soportados

- **Texto formateado**: bold, italic, underline, strike, code
- **Enlaces**: con atributo `link`
- **Colores de texto**: con atributo `color-class`
- **Listas**: bullet, ordered, checklist (checked/unchecked)
- **Bloques**: headers (h1-h3), blockquote, code-block
- **Menciones**: tipo `tag` con información de usuario
- **Separadores**: tipo `divider`

## Ejemplo Completo

```php
use Nitsnets\FilamentWysiwygJsonFormatter\Forms\Components\WysiwygEditor;
use App\Models\User;

WysiwygEditor::make('comment_content')
    ->label('Comentario')
    ->placeholder('Escribe un comentario o pulsa «@» para mencionar usuarios')
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

## Validación

El componente es compatible con todas las reglas de validación de Filament:

```php
WysiwygEditor::make('content')
    ->required()
    ->minLength(10)
    ->maxLength(5000)
```

## Traducciones

El plugin incluye traducciones en **español** e **inglés** por defecto.

### Idiomas Disponibles

- 🇪🇸 Español (`es`)
- 🇬🇧 Inglés (`en`)

### Publicar Traducciones

Si deseas personalizar las traducciones, puedes publicar los archivos de idioma:

```bash
php artisan vendor:publish --tag=filament-wysiwyg-json-formatter-translations
```

Esto copiará los archivos de traducción a `lang/vendor/filament-wysiwyg-json-formatter/` donde podrás modificarlos.

### Añadir Nuevos Idiomas

Para añadir soporte a un nuevo idioma:

1. Crea un nuevo directorio en `lang/vendor/filament-wysiwyg-json-formatter/` con el código del idioma (ej: `fr`, `de`, `pt`)
2. Copia el contenido de `es/wysiwyg.php` o `en/wysiwyg.php`
3. Traduce los textos al nuevo idioma

### Estructura de Traducciones

```php
return [
    'toolbar' => [
        'bold' => 'Bold',
        'italic' => 'Italic',
        // ... más botones
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

## Estilos

Los estilos se cargan de forma asíncrona solo cuando el componente se utiliza (`loadedOnRequest`), lo que optimiza el rendimiento:

- Soporte completo para dark mode
- CSS optimizado con PostCSS y cssnano
- Diseño responsive
- Animaciones y transiciones suaves

## Compatibilidad

- **PHP**: ^8.2
- **Filament**: ^4.0
- **Laravel**: ^11.0

## Licencia

MIT

## Créditos

Desarrollado por [Nitsnets](https://nitsnets.com)

