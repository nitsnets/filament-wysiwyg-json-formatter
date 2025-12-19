@props(['toolbarButtons', 'hasMentions' => false])

<div class="fi-fo-wysiwyg-toolbar">
    @if (in_array('bold', $toolbarButtons))
        <x-filament::icon-button
            icon="lucide-bold"
            size="sm"
            x-bind:color="activeStyles.bold ? 'primary' : 'gray'"
            x-on:click.prevent="formatText('bold')"
            :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.bold')"
            tabindex="-1"
        />
    @endif

    @if (in_array('italic', $toolbarButtons))
        <x-filament::icon-button
            icon="lucide-italic"
            size="sm"
            x-bind:color="activeStyles.italic ? 'primary' : 'gray'"
            x-on:click.prevent="formatText('italic')"
            :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.italic')"
            tabindex="-1"
        />
    @endif

    @if (in_array('underline', $toolbarButtons))
        <x-filament::icon-button
            icon="lucide-underline"
            size="sm"
            x-bind:color="activeStyles.underline ? 'primary' : 'gray'"
            x-on:click.prevent="formatText('underline')"
            :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.underline')"
            tabindex="-1"
        />
    @endif

    @if (in_array('strike', $toolbarButtons))
        <x-filament::icon-button
            icon="lucide-strikethrough"
            size="sm"
            x-bind:color="activeStyles.strike ? 'primary' : 'gray'"
            x-on:click.prevent="formatText('strikethrough')"
            :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.strikethrough')"
            tabindex="-1"
        />
    @endif

    @if (in_array('code', $toolbarButtons))
        <x-filament::icon-button
            icon="lucide-code-xml"
            size="sm"
            x-bind:color="activeStyles.code ? 'primary' : 'gray'"
            x-on:click.prevent="toggleInlineCode()"
            :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.code')"
            tabindex="-1"
        />
    @endif

    @if (in_array('codeBlock', $toolbarButtons))
        <x-filament::icon-button
            icon="lucide-code"
            size="sm"
            x-bind:color="activeStyles.codeBlock ? 'primary' : 'gray'"
            x-on:click.prevent="toggleCodeBlock()"
            :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.code_block')"
            tabindex="-1"
        />
    @endif

    {{-- Separador visual --}}
    @if (in_array('link', $toolbarButtons) || in_array('unorderedList', $toolbarButtons) || in_array('orderedList', $toolbarButtons) || in_array('checklist', $toolbarButtons) || in_array('divider', $toolbarButtons))
        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
    @endif

    @if (in_array('link', $toolbarButtons))
        <x-filament::icon-button
            icon="lucide-link"
            size="sm"
            color="gray"
            x-on:click.prevent="insertLink()"
            :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.link')"
            tabindex="-1"
        />
    @endif

    @if (in_array('unorderedList', $toolbarButtons))
        <x-filament::icon-button
            icon="lucide-list"
            size="sm"
            color="gray"
            x-on:click.prevent="formatText('insertUnorderedList')"
            :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.unordered_list')"
            tabindex="-1"
        />
    @endif

    @if (in_array('orderedList', $toolbarButtons))
        <x-filament::icon-button
            icon="lucide-list-ordered"
            size="sm"
            color="gray"
            x-on:click.prevent="formatText('insertOrderedList')"
            :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.ordered_list')"
            tabindex="-1"
        />
    @endif

    @if (in_array('checklist', $toolbarButtons))
        <x-filament::icon-button
            icon="lucide-list-todo"
            size="sm"
            color="gray"
            x-on:click.prevent="toggleChecklist()"
            :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.checklist')"
            tabindex="-1"
        />
    @endif

    @if (in_array('divider', $toolbarButtons))
        <x-filament::icon-button
            icon="lucide-minus"
            size="sm"
            color="gray"
            x-on:click.prevent="insertDivider()"
            :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.divider')"
            tabindex="-1"
        />
    @endif

    {{-- Separador visual --}}
    @if (in_array('h1', $toolbarButtons) || in_array('h2', $toolbarButtons) || in_array('h3', $toolbarButtons) || in_array('blockquote', $toolbarButtons) || in_array('textColor', $toolbarButtons))
        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
    @endif

    @if (in_array('h1', $toolbarButtons))
        <x-filament::icon-button
            icon="lucide-heading-1"
            size="sm"
            x-bind:color="activeStyles.h1 ? 'primary' : 'gray'"
            x-on:click.prevent="toggleHeader(1)"
            :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.heading_1')"
            tabindex="-1"
        />
    @endif

    @if (in_array('h2', $toolbarButtons))
        <x-filament::icon-button
            icon="lucide-heading-2"
            size="sm"
            x-bind:color="activeStyles.h2 ? 'primary' : 'gray'"
            x-on:click.prevent="toggleHeader(2)"
            :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.heading_2')"
            tabindex="-1"
        />
    @endif

    @if (in_array('h3', $toolbarButtons))
        <x-filament::icon-button
            icon="lucide-heading-3"
            size="sm"
            x-bind:color="activeStyles.h3 ? 'primary' : 'gray'"
            x-on:click.prevent="toggleHeader(3)"
            :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.heading_3')"
            tabindex="-1"
        />
    @endif

    @if (in_array('blockquote', $toolbarButtons))
        <x-filament::icon-button
            icon="lucide-quote"
            size="sm"
            x-bind:color="activeStyles.blockquote ? 'primary' : 'gray'"
            x-on:click.prevent="toggleBlockquote()"
            :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.blockquote')"
            tabindex="-1"
        />
    @endif

    @if (in_array('textColor', $toolbarButtons))
        @include('filament-wysiwyg-json-formatter::forms.components.partials.color-picker')
    @endif

    @if ($hasMentions)
        <x-filament::icon-button
            icon="lucide-at-sign"
            size="sm"
            color="gray"
            x-on:click.prevent="toggleMentionsPanel()"
            :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.mentions.title')"
            tabindex="-1"
        />
    @endif

    <x-filament::icon-button
        icon="lucide-code"
        size="sm"
        x-on:click.prevent="toggleHtmlPreview()"
        x-bind:color="showHtmlPreview ? 'primary' : 'gray'"
        :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.preview_json')"
        tabindex="-1"
    />
</div>

