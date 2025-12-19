<div class="relative" x-data="{ showColorPicker: false }">
    <x-filament::icon-button
        icon="lucide-palette"
        size="sm"
        x-bind:color="currentTextColor ? 'primary' : 'gray'"
        x-on:click.prevent="showColorPicker = !showColorPicker"
        :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.text_color')"
        tabindex="-1"
    />
    <div 
        x-show="showColorPicker" 
        x-cloak
        x-on:click.outside="showColorPicker = false"
        class="fi-fo-wysiwyg-color-picker"
    >
        <button type="button" x-on:click="setTextColor(null); showColorPicker = false" class="fi-fo-wysiwyg-color-btn fi-fo-wysiwyg-color-none" title="Sin color">✕</button>
        <button type="button" x-on:click="setTextColor('red'); showColorPicker = false" class="fi-fo-wysiwyg-color-btn" style="background-color: #ef4444;" title="Rojo"></button>
        <button type="button" x-on:click="setTextColor('orange'); showColorPicker = false" class="fi-fo-wysiwyg-color-btn" style="background-color: #f97316;" title="Naranja"></button>
        <button type="button" x-on:click="setTextColor('yellow'); showColorPicker = false" class="fi-fo-wysiwyg-color-btn" style="background-color: #eab308;" title="Amarillo"></button>
        <button type="button" x-on:click="setTextColor('green'); showColorPicker = false" class="fi-fo-wysiwyg-color-btn" style="background-color: #22c55e;" title="Verde"></button>
        <button type="button" x-on:click="setTextColor('blue'); showColorPicker = false" class="fi-fo-wysiwyg-color-btn" style="background-color: #3b82f6;" title="Azul"></button>
        <button type="button" x-on:click="setTextColor('purple'); showColorPicker = false" class="fi-fo-wysiwyg-color-btn" style="background-color: #a855f7;" title="Morado"></button>
        <button type="button" x-on:click="setTextColor('pink'); showColorPicker = false" class="fi-fo-wysiwyg-color-btn" style="background-color: #ec4899;" title="Rosa"></button>
        <button type="button" x-on:click="setTextColor('gray'); showColorPicker = false" class="fi-fo-wysiwyg-color-btn" style="background-color: #6b7280;" title="Gris"></button>
        <button type="button" x-on:click="setTextColor('black'); showColorPicker = false" class="fi-fo-wysiwyg-color-btn" style="background-color: #000000;" title="Negro"></button>
    </div>
</div>

