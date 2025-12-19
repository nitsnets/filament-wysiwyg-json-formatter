# Estructura del Paquete WYSIWYG JSON Formatter

## 📁 Estructura de Archivos

```
filament-wysiwyg-json-formatter/
├── resources/
│   ├── css/
│   │   └── index.css                    # CSS fuente (se compila)
│   ├── dist/
│   │   └── wysiwyg.css                  # CSS compilado (9.9KB)
│   ├── lang/
│   │   ├── en/wysiwyg.php              # Traducciones inglés
│   │   └── es/wysiwyg.php              # Traducciones español
│   └── views/
│       └── forms/components/
│           ├── wysiwyg-editor.blade.php     # Vista principal
│           └── partials/
│               ├── toolbar.blade.php         # Barra de herramientas
│               ├── color-picker.blade.php    # Selector de colores
│               ├── mentions-panel.blade.php  # Panel de menciones
│               ├── json-preview.blade.php    # Preview del JSON
│               └── load-styles.blade.php     # Carga de CSS asíncrona
├── src/
│   ├── FilamentWysiwygJsonFormatterServiceProvider.php
│   └── Forms/Components/
│       └── WysiwygEditor.php
├── config/
│   └── wysiwyg.php                     # Configuración del paquete
├── package.json                        # Dependencias npm
├── postcss.config.cjs                  # Configuración PostCSS
└── composer.json                       # Dependencias PHP
```

## 🎨 Sistema de Estilos

### Carga de CSS
El CSS se carga **asíncronamente** solo cuando el componente se usa, utilizando:

```blade
<div x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('wysiwyg', package: 'filament-wysiwyg-json-formatter'))]"></div>
```

### Compilación de CSS
```bash
# Instalar dependencias
npm install

# Compilar CSS (PostCSS + cssnano)
npm run build
```

Esto genera `resources/dist/wysiwyg.css` optimizado y minificado.

### Registro del Asset
El CSS está registrado en el `ServiceProvider`:

```php
FilamentAsset::register([
    Css::make('wysiwyg', __DIR__.'/../resources/dist/wysiwyg.css')
        ->loadedOnRequest(),
], 'filament-wysiwyg-json-formatter');
```

## 🧩 Componentes Parciales

### 1. **toolbar.blade.php**
- Todos los botones de formato
- Separadores visuales
- Botones condicionales según `$toolbarButtons`

**Props:**
- `$toolbarButtons` (array): Botones a mostrar
- `$hasMentions` (bool): Si mostrar botón de menciones

### 2. **color-picker.blade.php**
- Paleta de colores desplegable
- 9 colores + opción sin color
- Alpine.js para mostrar/ocultar

### 3. **mentions-panel.blade.php**
- Panel de búsqueda de usuarios
- Lista filtrable de menciones
- Navegación con teclado

### 4. **json-preview.blade.php**
- Vista previa del JSON generado
- Modal colapsable
- Código formateado

### 5. **load-styles.blade.php**
- Carga asíncrona del CSS
- Usa `x-load-css` de Filament
- Solo se carga cuando se necesita

## 🔧 Configuración

### config/wysiwyg.php
```php
return [
    'default_toolbar_buttons' => [...],  // Botones por defecto
    'mentions_enabled' => true,          // Habilitar menciones
    'dark_mode' => true,                 // Soporte dark mode
];
```

## 📝 Uso de Componentes Parciales

Para incluir un componente parcial en la vista principal:

```blade
{{-- Cargar estilos --}}
@include('filament-wysiwyg-json-formatter::forms.components.partials.load-styles')

{{-- Toolbar --}}
@include('filament-wysiwyg-json-formatter::forms.components.partials.toolbar', [
    'toolbarButtons' => $toolbarButtons,
    'hasMentions' => $hasMentions(),
])

{{-- Panel de menciones --}}
@if ($hasMentions())
    @include('filament-wysiwyg-json-formatter::forms.components.partials.mentions-panel')
@endif

{{-- JSON Preview --}}
@include('filament-wysiwyg-json-formatter::forms.components.partials.json-preview')
```

## 🚀 Beneficios de esta Estructura

✅ **CSS Optimizado**: Minificado con cssnano (9.9KB)
✅ **Carga Asíncrona**: Solo cuando se usa el componente
✅ **Componentes Reutilizables**: Fácil de mantener y extender
✅ **Dark Mode**: Totalmente soportado
✅ **Traducciones**: Español e Inglés incluidos
✅ **TypeSafe**: Props bien definidos

## 🔄 Flujo de Desarrollo

1. Editar `resources/css/index.css`
2. Ejecutar `npm run build`
3. El CSS compilado se guarda en `resources/dist/wysiwyg.css`
4. Filament lo carga automáticamente cuando se usa el componente

## 📦 Dependencias

### NPM
- `postcss` - Procesador CSS
- `postcss-cli` - CLI para PostCSS
- `postcss-nesting` - Soporte para CSS anidado
- `cssnano` - Minificación y optimización

### Composer
- `spatie/laravel-package-tools` - Herramientas para paquetes Laravel
- `filament/filament` - Framework Filament v4

