<div
    x-show="showHtmlPreview"
    x-cloak
    class="fi-fo-wysiwyg-html-preview"
>
    <div class="fi-fo-wysiwyg-html-preview-header">
        <span>{{ __('filament-wysiwyg-json-formatter::wysiwyg.json_preview.title') }}</span>
        <button
            type="button"
            @click.prevent="toggleHtmlPreview()"
            class="fi-fo-wysiwyg-html-preview-close"
            tabindex="-1"
            :title="__('filament-wysiwyg-json-formatter::wysiwyg.json_preview.close')"
        >
            ×
        </button>
    </div>
    <pre class="fi-fo-wysiwyg-html-preview-content" x-text="htmlPreview"></pre>
</div>

