<div
    x-show="showMentionsPanel"
    x-cloak
    @click.outside="showMentionsPanel = false"
    class="fi-fo-wysiwyg-mentions-panel"
>
    <div class="fi-fo-wysiwyg-mentions-header">
        <span>{{ __('filament-wysiwyg-json-formatter::wysiwyg.mentions.title') }}</span>
        <button
            type="button"
            @click.prevent="showMentionsPanel = false"
            class="fi-fo-wysiwyg-mentions-close"
            tabindex="-1"
            :title="__('filament-wysiwyg-json-formatter::wysiwyg.mentions.close')"
        >
            ×
        </button>
    </div>

    <div class="p-2">
        <input
            type="text"
            x-model="mentionSearch"
            placeholder="Buscar..."
            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-200"
        >
    </div>

    <div class="fi-fo-wysiwyg-mentions-list">
        <template x-for="(mention, index) in filteredMentions" :key="mention.id">
            <button
                type="button"
                @click.prevent="insertMention(mention.id, mention.label)"
                @mouseenter="selectedMentionIndex = index"
                class="fi-fo-wysiwyg-mention-item"
                :class="{ 'fi-fo-wysiwyg-mention-item-selected': index === selectedMentionIndex }"
                tabindex="-1"
            >
                <span x-text="'@' + mention.label"></span>
            </button>
        </template>

        <div x-show="filteredMentions.length === 0" class="p-3 text-sm text-gray-500 dark:text-gray-400 text-center">
            {{ __('filament-wysiwyg-json-formatter::wysiwyg.mentions.no_users_found') }}
        </div>
    </div>
</div>

