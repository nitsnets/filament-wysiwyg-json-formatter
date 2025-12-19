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
│               └── json-preview.blade.php    # Preview del JSON
├── src/
│   ├── FilamentWysiwygJsonFormatterServiceProvider.php
│   └── Forms/Components/
│       └── WysiwygJsonFormatter.php
├── config/
│   └── wysiwyg.php                     # Configuración del paquete
├── package.json                        # Dependencias npm
├── postcss.config.cjs                  # Configuración PostCSS
└── composer.json                       # Dependencias PHP
```

## 🎨 Sistema de Estilos

### Compilación de CSS
```bash
# Instalar dependencias
npm install

# Compilar CSS (PostCSS + cssnano)
npm run build
```

Esto genera `resources/dist/wysiwyg.css` optimizado y minificado.

## 🔧 Configuración

### config/wysiwyg.php
```php
return [
    'default_toolbar_buttons' => [...],  // Botones por defecto
];
```

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

