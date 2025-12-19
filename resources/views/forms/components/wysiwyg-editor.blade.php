@php
    $id = $getId();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $toolbarButtons = $getToolbarButtons();
    $mentions = $hasMentions() ? $getMentions() : [];
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('wysiwyg', package: 'nitsnets/filament-wysiwyg-json-formatter'))]"
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            isDisabled: @js($isDisabled),
            toolbarButtons: @js($toolbarButtons),
            mentions: @js($mentions),
            hasMentions: @js($hasMentions()),
            showMentionsPanel: false,
            mentionSearch: '',
            selectedMentionIndex: 0,
            showHtmlPreview: false,
            isInternalUpdate: false,
            activeStyles: {
                bold: false,
                italic: false,
                underline: false,
                strike: false,
                code: false,
                codeBlock: false,
                h1: false,
                h2: false,
                h3: false,
                blockquote: false
            },
            currentTextColor: null,

            init() {
                this.$nextTick(() => {
                    const editor = this.$refs.editor;
                    if (!editor) return;

                    if (this.state) {
                        const html = this.getHtmlFromState(this.state);
                        editor.innerHTML = html || '';
                    }

                    // Manejar clics en el checklist para marcar/desmarcar
                    editor.addEventListener('click', (e) => {
                        if (this.isDisabled) return;

                        const li = e.target.closest('li');
                        if (li && li.parentNode && li.parentNode.tagName === 'UL' && li.parentNode.classList.contains('checklist')) {
                            const rect = li.getBoundingClientRect();
                            // Si el clic es en la parte izquierda (donde está el checkbox visual)
                            if (e.clientX < rect.left + 25) {
                                const isChecked = li.getAttribute('data-checked') === 'true';
                                li.setAttribute('data-checked', isChecked ? 'false' : 'true');
                                this.updateState();
                            }
                        }
                    });

                    // Detectar listas automáticas con guión, asterisco o número
                    editor.addEventListener('input', (e) => {
                        if (this.isDisabled) return;

                        // Solo procesar cuando se inserta texto
                        if (e.inputType !== 'insertText' || !e.data) return;

                        // Solo actuar cuando se inserta un espacio
                        if (e.data !== ' ') return;

                        const selection = window.getSelection();
                        if (!selection.rangeCount) return;

                        const range = selection.getRangeAt(0);
                        let node = range.startContainer;

                        // Si no es un nodo de texto, buscar el nodo de texto
                        if (node.nodeType !== Node.TEXT_NODE) {
                            if (node.childNodes.length > 0) {
                                for (let i = 0; i < node.childNodes.length; i++) {
                                    if (node.childNodes[i].nodeType === Node.TEXT_NODE) {
                                        node = node.childNodes[i];
                                        break;
                                    }
                                }
                            }
                            if (node.nodeType !== Node.TEXT_NODE) return;
                        }

                        const textContent = node.textContent || '';

                        // Buscar si hay -, * o 1. al inicio del texto
                        let match = textContent.match(/^(\s*)(-|\*)\s/);
                        let isOrdered = false;

                        if (!match) {
                            // Intentar detectar lista numerada (1. , 2. , etc.)
                            match = textContent.match(/^(\s*)(\d+)\.\s/);
                            if (match) {
                                isOrdered = true;
                            }
                        }

                        if (!match) return;

                        // Verificar que no estamos ya en una lista
                        const parentElement = node.parentElement;
                        if (!parentElement) return;
                        if (parentElement.closest('ul') || parentElement.closest('ol')) return;

                        // Encontrar el elemento de bloque contenedor
                        let blockElement = parentElement.closest('p') ||
                                         parentElement.closest('div') ||
                                         parentElement.closest('h1') ||
                                         parentElement.closest('h2') ||
                                         parentElement.closest('h3');

                        // Si no hay blockElement o es el editor mismo, usar el parentElement directamente
                        if (!blockElement || blockElement === editor) {
                            blockElement = parentElement;
                        }

                        // Si el blockElement es el editor, salir
                        if (blockElement === editor) return;
                        if (!blockElement.parentNode) return;

                        // Obtener el texto después del marcador de lista
                        const restOfText = textContent.substring(match[0].length);

                        // Crear la lista (ordenada o no ordenada)
                        const listElement = document.createElement(isOrdered ? 'ol' : 'ul');
                        const li = document.createElement('li');

                        if (restOfText.trim()) {
                            li.textContent = restOfText;
                        } else {
                            const br = document.createElement('br');
                            li.appendChild(br);
                        }

                        listElement.appendChild(li);

                        // Reemplazar el bloque con la lista
                        try {
                            blockElement.parentNode.replaceChild(listElement, blockElement);

                            // Posicionar el cursor al inicio del li
                            const newRange = document.createRange();
                            if (li.firstChild && li.firstChild.nodeType === Node.TEXT_NODE) {
                                newRange.setStart(li.firstChild, 0);
                            } else {
                                newRange.setStart(li, 0);
                            }
                            newRange.collapse(true);
                            selection.removeAllRanges();
                            selection.addRange(newRange);

                            // Actualizar el estado
                            this.$nextTick(() => {
                                this.updateState();
                            });
                        } catch (error) {
                            console.error('Error al crear lista:', error);
                        }
                    });

                    // Manejar teclado para comportamientos especiales en bloques de código y citas
                    editor.addEventListener('keydown', (e) => {
                        if (this.isDisabled) return;

                        if (e.key === 'Enter') {
                            const selection = window.getSelection();
                            if (!selection.rangeCount) return;

                            const range = selection.getRangeAt(0);
                            const startContainer = range.startContainer;
                            const pre = startContainer.nodeType === Node.TEXT_NODE
                                ? startContainer.parentElement?.closest('pre')
                                : startContainer.closest('pre');
                            const blockquote = startContainer.nodeType === Node.TEXT_NODE
                                ? startContainer.parentElement?.closest('blockquote')
                                : startContainer.closest('blockquote');

                            // Detectar código en línea (pero no dentro de pre)
                            const code = !pre && (startContainer.nodeType === Node.TEXT_NODE
                                ? startContainer.parentElement?.closest('code')
                                : startContainer.closest('code'));

                            // Manejar código en línea: salir al presionar Enter
                            if (code) {
                                e.preventDefault();

                                // Crear un salto de línea y salir del code
                                const br = document.createElement('br');
                                const textNode = document.createTextNode('\u00A0'); // Espacio no rompible para posicionar cursor

                                // Insertar después del elemento code
                                if (code.parentNode) {
                                    // Si estamos al final del code
                                    const atEnd = range.endOffset >= (range.endContainer.textContent?.length || 0);

                                    if (atEnd) {
                                        // Insertar después del code
                                        code.parentNode.insertBefore(br, code.nextSibling);
                                        if (br.nextSibling) {
                                            code.parentNode.insertBefore(textNode, br.nextSibling);
                                        } else {
                                            code.parentNode.appendChild(textNode);
                                        }

                                        // Posicionar cursor después del br
                                        const newRange = document.createRange();
                                        newRange.setStart(textNode, 0);
                                        newRange.collapse(true);
                                        selection.removeAllRanges();
                                        selection.addRange(newRange);
                                    } else {
                                        // Estamos en medio del code: dividirlo
                                        const textAfter = range.endContainer.textContent.substring(range.endOffset);
                                        range.endContainer.textContent = range.endContainer.textContent.substring(0, range.endOffset);

                                        // Crear nuevo code con el texto restante
                                        const newCode = document.createElement('code');
                                        newCode.textContent = textAfter;

                                        // Insertar: br, espacio, nuevo code
                                        code.parentNode.insertBefore(br, code.nextSibling);
                                        code.parentNode.insertBefore(textNode, br.nextSibling);
                                        code.parentNode.insertBefore(newCode, textNode.nextSibling);

                                        // Posicionar cursor en el espacio entre los codes
                                        const newRange = document.createRange();
                                        newRange.setStart(textNode, 0);
                                        newRange.collapse(true);
                                        selection.removeAllRanges();
                                        selection.addRange(newRange);
                                    }
                                }

                                this.updateState();
                                return;
                            }

                            // Manejar bloques de código
                            if (pre) {
                                e.preventDefault();

                                // Obtener el texto completo del bloque ANTES de cualquier modificación
                                const preText = pre.textContent || '';

                                // Calcular la posición del cursor dentro del texto completo del PRE
                                let cursorPosition = 0;
                                const walker = document.createTreeWalker(
                                    pre,
                                    NodeFilter.SHOW_TEXT,
                                    null
                                );

                                let textNode;
                                let foundNode = null;
                                while (textNode = walker.nextNode()) {
                                    if (textNode === startContainer) {
                                        foundNode = textNode;
                                        cursorPosition += range.startOffset;
                                        break;
                                    }
                                    cursorPosition += textNode.textContent.length;
                                }

                                // Si no encontramos el nodo, usar el texto completo como posición
                                if (!foundNode && pre.childNodes.length > 0) {
                                    // Buscar el nodo de texto más cercano
                                    const allTextNodes = [];
                                    const allWalker = document.createTreeWalker(
                                        pre,
                                        NodeFilter.SHOW_TEXT,
                                        null
                                    );
                                    let node;
                                    while (node = allWalker.nextNode()) {
                                        allTextNodes.push(node);
                                    }
                                    if (allTextNodes.length > 0) {
                                        foundNode = allTextNodes[allTextNodes.length - 1];
                                        cursorPosition = preText.length;
                                    }
                                }

                                // Encontrar el inicio de la línea actual (desde el último \n o inicio)
                                let lineStart = 0;
                                for (let i = cursorPosition - 1; i >= 0; i--) {
                                    if (preText[i] === '\n') {
                                        lineStart = i + 1;
                                        break;
                                    }
                                }

                                // Encontrar el final de la línea actual (hasta el siguiente \n o final)
                                let lineEnd = preText.length;
                                for (let i = cursorPosition; i < preText.length; i++) {
                                    if (preText[i] === '\n') {
                                        lineEnd = i;
                                        break;
                                    }
                                }

                                // Obtener el contenido de la línea actual
                                const currentLine = preText.substring(lineStart, lineEnd);
                                const isLineEmpty = currentLine.trim() === '';

                                // Verificar si el cursor está al final de la línea
                                const isAtEndOfLine = cursorPosition >= lineEnd;

                                if (isLineEmpty && isAtEndOfLine && !e.shiftKey) {
                                    // La línea está vacía y el cursor está al final: salir del bloque (solo sin Shift)
                                    // Eliminar la línea vacía del bloque
                                    let beforeLine = preText.substring(0, lineStart);
                                    let afterLine = preText.substring(lineEnd);

                                    // Si hay un salto de línea antes de la línea vacía, eliminarlo también
                                    if (beforeLine.endsWith('\n')) {
                                        beforeLine = beforeLine.slice(0, -1);
                                    }

                                    const finalText = beforeLine + afterLine;

                                    // Si el bloque está completamente vacío, eliminarlo
                                    if (finalText.trim() === '') {
                                        const p = document.createElement('p');
                                        p.innerHTML = '<br>';
                                        pre.parentNode.replaceChild(p, pre);

                                        const newRange = document.createRange();
                                        newRange.setStart(p, 0);
                                        newRange.collapse(true);
                                        selection.removeAllRanges();
                                        selection.addRange(newRange);
                                    } else {
                                        // Actualizar el texto del bloque sin la línea vacía
                                        pre.textContent = finalText;

                                        // Crear párrafo después del PRE
                                        const p = document.createElement('p');
                                        p.innerHTML = '<br>';
                                        pre.parentNode.insertBefore(p, pre.nextSibling);

                                        // Mover cursor al nuevo párrafo
                                        const newRange = document.createRange();
                                        newRange.setStart(p, 0);
                                        newRange.collapse(true);
                                        selection.removeAllRanges();
                                        selection.addRange(newRange);
                                    }

                                    this.updateState();
                                } else {
                                    // La línea tiene contenido o Shift+Enter: insertar salto(s) de línea
                                    const numberOfNewlines = e.shiftKey ? 1 : 2;

                                    // Calcular la nueva posición del cursor después de insertar
                                    const newCursorPosition = cursorPosition + numberOfNewlines;

                                    // Insertar los saltos de línea usando textContent (más confiable)
                                    const beforeCursor = preText.substring(0, cursorPosition);
                                    const afterCursor = preText.substring(cursorPosition);
                                    pre.textContent = beforeCursor + '\n'.repeat(numberOfNewlines) + afterCursor;

                                    // Recalcular la posición del cursor después de la inserción
                                    const newWalker = document.createTreeWalker(
                                        pre,
                                        NodeFilter.SHOW_TEXT,
                                        null
                                    );
                                    let currentPos = 0;
                                    let targetNode = null;
                                    let targetOffset = 0;

                                    let node;
                                    while (node = newWalker.nextNode()) {
                                        const textLength = node.textContent.length;
                                        if (currentPos + textLength >= newCursorPosition) {
                                            targetNode = node;
                                            targetOffset = newCursorPosition - currentPos;
                                            break;
                                        }
                                        currentPos += textLength;
                                    }

                                    // Si no encontramos el nodo, usar el último nodo de texto
                                    if (!targetNode) {
                                        const allNodes = [];
                                        const allWalker = document.createTreeWalker(
                                            pre,
                                            NodeFilter.SHOW_TEXT,
                                            null
                                        );
                                        let n;
                                        while (n = allWalker.nextNode()) {
                                            allNodes.push(n);
                                        }
                                        if (allNodes.length > 0) {
                                            targetNode = allNodes[allNodes.length - 1];
                                            targetOffset = targetNode.textContent.length;
                                        }
                                    }

                                    // Posicionar el cursor
                                    if (targetNode) {
                                        const newRange = document.createRange();
                                        newRange.setStart(targetNode, Math.min(targetOffset, targetNode.textContent.length));
                                        newRange.collapse(true);
                                        selection.removeAllRanges();
                                        selection.addRange(newRange);
                                    }

                                    this.updateState();
                                }
                                return;
                            }

                            // Manejar blockquotes (citas) con la misma lógica
                            if (blockquote) {
                                e.preventDefault();

                                // Obtener el texto completo del bloquequote ANTES de cualquier modificación
                                const blockquoteText = blockquote.textContent || '';

                                // Calcular la posición del cursor dentro del texto completo del blockquote
                                let cursorPosition = 0;
                                const walker = document.createTreeWalker(
                                    blockquote,
                                    NodeFilter.SHOW_TEXT,
                                    null
                                );

                                let textNode;
                                let foundNode = null;
                                while (textNode = walker.nextNode()) {
                                    if (textNode === startContainer) {
                                        foundNode = textNode;
                                        cursorPosition += range.startOffset;
                                        break;
                                    }
                                    cursorPosition += textNode.textContent.length;
                                }

                                // Si no encontramos el nodo, usar el texto completo como posición
                                if (!foundNode && blockquote.childNodes.length > 0) {
                                    // Buscar el nodo de texto más cercano
                                    const allTextNodes = [];
                                    const allWalker = document.createTreeWalker(
                                        blockquote,
                                        NodeFilter.SHOW_TEXT,
                                        null
                                    );
                                    let node;
                                    while (node = allWalker.nextNode()) {
                                        allTextNodes.push(node);
                                    }
                                    if (allTextNodes.length > 0) {
                                        foundNode = allTextNodes[allTextNodes.length - 1];
                                        cursorPosition = blockquoteText.length;
                                    }
                                }

                                // Encontrar el inicio de la línea actual (desde el último \n o inicio)
                                let lineStart = 0;
                                for (let i = cursorPosition - 1; i >= 0; i--) {
                                    if (blockquoteText[i] === '\n') {
                                        lineStart = i + 1;
                                        break;
                                    }
                                }

                                // Encontrar el final de la línea actual (hasta el siguiente \n o final)
                                let lineEnd = blockquoteText.length;
                                for (let i = cursorPosition; i < blockquoteText.length; i++) {
                                    if (blockquoteText[i] === '\n') {
                                        lineEnd = i;
                                        break;
                                    }
                                }

                                // Obtener el contenido de la línea actual
                                const currentLine = blockquoteText.substring(lineStart, lineEnd);
                                const isLineEmpty = currentLine.trim() === '';

                                // Verificar si el cursor está al final de la línea
                                const isAtEndOfLine = cursorPosition >= lineEnd;

                                if (isLineEmpty && isAtEndOfLine && !e.shiftKey) {
                                    // La línea está vacía y el cursor está al final: salir del blockquote (solo sin Shift)
                                    // Eliminar la línea vacía del blockquote
                                    let beforeLine = blockquoteText.substring(0, lineStart);
                                    let afterLine = blockquoteText.substring(lineEnd);

                                    // Si hay un salto de línea antes de la línea vacía, eliminarlo también
                                    if (beforeLine.endsWith('\n')) {
                                        beforeLine = beforeLine.slice(0, -1);
                                    }

                                    const finalText = beforeLine + afterLine;

                                    // Si el blockquote está completamente vacío, eliminarlo
                                    if (finalText.trim() === '') {
                                        const p = document.createElement('p');
                                        p.innerHTML = '<br>';
                                        blockquote.parentNode.replaceChild(p, blockquote);

                                        const newRange = document.createRange();
                                        newRange.setStart(p, 0);
                                        newRange.collapse(true);
                                        selection.removeAllRanges();
                                        selection.addRange(newRange);
                                    } else {
                                        // Actualizar el texto del blockquote sin la línea vacía
                                        const className = blockquote.className;
                                        blockquote.textContent = finalText;
                                        if (className) blockquote.className = className;

                                        // Crear párrafo después del blockquote
                                        const p = document.createElement('p');
                                        p.innerHTML = '<br>';
                                        blockquote.parentNode.insertBefore(p, blockquote.nextSibling);

                                        // Mover cursor al nuevo párrafo
                                        const newRange = document.createRange();
                                        newRange.setStart(p, 0);
                                        newRange.collapse(true);
                                        selection.removeAllRanges();
                                        selection.addRange(newRange);
                                    }

                                    this.updateState();
                                } else {
                                    // La línea tiene contenido o Shift+Enter: insertar salto(s) de línea
                                    const numberOfNewlines = e.shiftKey ? 1 : 2;

                                    // Calcular la nueva posición del cursor después de insertar
                                    const newCursorPosition = cursorPosition + numberOfNewlines;

                                    // Guardar la clase CSS antes de modificar textContent
                                    const className = blockquote.className;

                                    // Insertar los saltos de línea usando textContent (más confiable)
                                    const beforeCursor = blockquoteText.substring(0, cursorPosition);
                                    const afterCursor = blockquoteText.substring(cursorPosition);
                                    blockquote.textContent = beforeCursor + '\n'.repeat(numberOfNewlines) + afterCursor;

                                    // Restaurar la clase CSS
                                    if (className) blockquote.className = className;

                                    // Recalcular la posición del cursor después de la inserción
                                    const newWalker = document.createTreeWalker(
                                        blockquote,
                                        NodeFilter.SHOW_TEXT,
                                        null
                                    );
                                    let currentPos = 0;
                                    let targetNode = null;
                                    let targetOffset = 0;

                                    let node;
                                    while (node = newWalker.nextNode()) {
                                        const textLength = node.textContent.length;
                                        if (currentPos + textLength >= newCursorPosition) {
                                            targetNode = node;
                                            targetOffset = newCursorPosition - currentPos;
                                            break;
                                        }
                                        currentPos += textLength;
                                    }

                                    // Si no encontramos el nodo, usar el último nodo de texto
                                    if (!targetNode) {
                                        const allNodes = [];
                                        const allWalker = document.createTreeWalker(
                                            blockquote,
                                            NodeFilter.SHOW_TEXT,
                                            null
                                        );
                                        let n;
                                        while (n = allWalker.nextNode()) {
                                            allNodes.push(n);
                                        }
                                        if (allNodes.length > 0) {
                                            targetNode = allNodes[allNodes.length - 1];
                                            targetOffset = targetNode.textContent.length;
                                        }
                                    }

                                    // Posicionar el cursor
                                    if (targetNode) {
                                        const newRange = document.createRange();
                                        newRange.setStart(targetNode, Math.min(targetOffset, targetNode.textContent.length));
                                        newRange.collapse(true);
                                        selection.removeAllRanges();
                                        selection.addRange(newRange);
                                    }

                                    this.updateState();
                                }
                                return;
                            }
                        }
                    });
                });

                this.$watch('state', (value) => {
                    if (this.isInternalUpdate) {
                        this.isInternalUpdate = false;
                        return;
                    }

                    // No actualizar si el editor tiene el foco (el usuario está escribiendo)
                    if (this.$refs.editor && document.activeElement === this.$refs.editor) {
                        return;
                    }

                    if (this.$refs.editor) {
                        const html = this.getHtmlFromState(value);
                        if (this.$refs.editor.innerHTML !== html) {
                            this.$refs.editor.innerHTML = html || '';
                        }
                    }
                });
            },

            // Obtener HTML desde el estado (soporta múltiples formatos)
            getHtmlFromState(state) {
                if (!state) return '';

                try {
                    // Si es objeto con formato ClickUp: {comment: [...]}
                    if (typeof state === 'object' && state.comment && Array.isArray(state.comment)) {
                        return this.clickUpJsonToHtml(state.comment);
                    }

                    // Si es array con formato: [{'comment': [...]}]
                    if (Array.isArray(state) && state.length > 0 && state[0] && state[0].comment) {
                        return this.clickUpJsonToHtml(state[0].comment);
                    }

                    // Si es array directo con formato ClickUp
                    if (Array.isArray(state) && state.length > 0 && state[0].text !== undefined) {
                        return this.clickUpJsonToHtml(state);
                    }

                    // Si es string HTML (legacy)
                    if (typeof state === 'string') {
                        return state;
                    }
                } catch (e) {
                    console.error('Error parsing state:', e);
                }

                return '';
            },

            updateState() {
                if (this.isDisabled || !this.$refs.editor) return;

                try {
                    const html = this.$refs.editor.innerHTML;
                    // Convertir HTML a formato JSON de ClickUp
                    const clickUpJson = this.htmlToClickUpJson(html);
                    const jsonString = JSON.stringify(clickUpJson);

                    if (JSON.stringify(this.state) !== jsonString) {
                        this.isInternalUpdate = true;
                        this.state = clickUpJson;
                    }
                    this.updateActiveStyles();
                } catch (e) {
                    console.error('Error updating state:', e);
                }
            },

            updateActiveStyles() {
                if (!this.$refs.editor || document.activeElement !== this.$refs.editor) return;
                try {
                    this.activeStyles.bold = document.queryCommandState('bold');
                    this.activeStyles.italic = document.queryCommandState('italic');
                    this.activeStyles.underline = document.queryCommandState('underline');
                    this.activeStyles.strike = document.queryCommandState('strikeThrough');

                    // Detección de elementos buscando en la jerarquía del cursor
                    let isCode = false;
                    let isCodeBlock = false;
                    let isH1 = false;
                    let isH2 = false;
                    let isH3 = false;
                    let isBlockquote = false;
                    let textColor = null;

                    const selection = window.getSelection();
                    if (selection.rangeCount > 0) {
                        let node = selection.getRangeAt(0).startContainer;
                        while (node && node !== this.$refs.editor) {
                            if (node.nodeType === Node.ELEMENT_NODE) {
                                const tag = node.tagName;
                                if (tag === 'CODE') isCode = true;
                                if (tag === 'PRE') isCodeBlock = true;
                                if (tag === 'H1') isH1 = true;
                                if (tag === 'H2') isH2 = true;
                                if (tag === 'H3') isH3 = true;
                                if (tag === 'BLOCKQUOTE') isBlockquote = true;
                                // Detectar color
                                if (node.dataset && node.dataset.colorClass) {
                                    textColor = node.dataset.colorClass;
                                }
                            }
                            node = node.parentNode;
                        }
                    }
                    this.activeStyles.code = isCode && !isCodeBlock;
                    this.activeStyles.codeBlock = isCodeBlock;
                    this.activeStyles.h1 = isH1;
                    this.activeStyles.h2 = isH2;
                    this.activeStyles.h3 = isH3;
                    this.activeStyles.blockquote = isBlockquote;
                    this.currentTextColor = textColor;
                } catch (e) {
                    // Ignorar errores
                }
            },

            toggleCodeBlock() {
                if (this.isDisabled || !this.$refs.editor) return;
                this.$refs.editor.focus();

                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    let container = range.commonAncestorContainer;
                    if (container.nodeType === Node.TEXT_NODE) container = container.parentNode;

                    const pre = container.closest('pre');
                    if (pre) {
                        // Si ya es un bloque de código, convertir a párrafo normal
                        document.execCommand('formatBlock', false, 'p');
                    } else {
                        // Si no es un bloque de código, envolver en pre
                        document.execCommand('formatBlock', false, 'pre');

                        // Asegurarnos de que tenga la clase correcta para el renderizado
                        const newSelection = window.getSelection();
                        if (newSelection.rangeCount > 0) {
                            let newNode = newSelection.getRangeAt(0).startContainer;
                            if (newNode.nodeType === Node.TEXT_NODE) newNode = newNode.parentNode;
                            const newPre = newNode.closest('pre');
                            if (newPre) newPre.className = 'fi-fo-wysiwyg-code-block';
                        }
                    }
                }
                this.updateState();
            },

            insertDivider() {
                if (this.isDisabled || !this.$refs.editor) return;
                this.$refs.editor.focus();

                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    range.deleteContents();

                    // Crear el separador
                    const hr = document.createElement('hr');
                    hr.className = 'fi-fo-wysiwyg-divider';

                    // Crear un salto de línea después para poder escribir
                    const br = document.createElement('br');

                    // Insertar en orden inverso debido a cómo funciona insertNode
                    range.insertNode(br);
                    range.insertNode(hr);

                    // Posicionar el cursor después del br
                    range.setStartAfter(br);
                    range.collapse(true);
                    selection.removeAllRanges();
                    selection.addRange(range);
                } else {
                    document.execCommand('insertHorizontalRule', false, null);
                }

                this.updateState();
            },

            toggleChecklist() {
                if (this.isDisabled || !this.$refs.editor) return;
                this.$refs.editor.focus();

                // Usar insertUnorderedList como base
                document.execCommand('insertUnorderedList', false, null);

                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    let node = range.startContainer;

                    // Buscar el UL padre
                    while (node && node !== this.$refs.editor) {
                        if (node.nodeType === Node.ELEMENT_NODE && node.tagName === 'UL') {
                            if (!node.classList.contains('checklist')) {
                                node.classList.add('checklist');
                                // Marcar todos los LI como unchecked por defecto si no tienen el atributo
                                Array.from(node.children).forEach(li => {
                                    if (!li.hasAttribute('data-checked')) {
                                        li.setAttribute('data-checked', 'false');
                                    }
                                });
                            } else {
                                node.classList.remove('checklist');
                                Array.from(node.children).forEach(li => li.removeAttribute('data-checked'));
                            }
                            break;
                        }
                        node = node.parentNode;
                    }
                }
                this.updateState();
            },

            toggleHeader(level) {
                if (this.isDisabled || !this.$refs.editor) return;
                this.$refs.editor.focus();

                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    let container = range.commonAncestorContainer;
                    if (container.nodeType === Node.TEXT_NODE) container = container.parentNode;

                    const currentHeader = container.closest('h1, h2, h3');
                    if (currentHeader && currentHeader.tagName === 'H' + level) {
                        // Si ya es el mismo header, convertir a párrafo
                        document.execCommand('formatBlock', false, 'p');
                    } else {
                        // Convertir a header
                        document.execCommand('formatBlock', false, 'h' + level);
                    }
                }
                this.updateState();
            },

            toggleBlockquote() {
                if (this.isDisabled || !this.$refs.editor) return;
                this.$refs.editor.focus();

                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);
                    let container = range.commonAncestorContainer;
                    if (container.nodeType === Node.TEXT_NODE) container = container.parentNode;

                    const blockquote = container.closest('blockquote');
                    if (blockquote) {
                        // Si ya es blockquote, convertir a párrafo
                        document.execCommand('formatBlock', false, 'p');
                    } else {
                        // Crear blockquote manualmente porque formatBlock no soporta blockquote en todos los navegadores
                        const selectedText = range.toString();
                        const bq = document.createElement('blockquote');
                        bq.className = 'fi-fo-wysiwyg-blockquote';
                        bq.textContent = selectedText || '\u00A0';

                        range.deleteContents();
                        range.insertNode(bq);

                        // Posicionar cursor dentro del blockquote
                        const newRange = document.createRange();
                        newRange.selectNodeContents(bq);
                        newRange.collapse(false);
                        selection.removeAllRanges();
                        selection.addRange(newRange);
                    }
                }
                this.updateState();
            },

            setTextColor(color) {
                if (this.isDisabled || !this.$refs.editor) return;
                this.$refs.editor.focus();

                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);

                    if (range.collapsed) {
                        // No hay selección
                        if (color) {
                            // Crear span con color y un espacio zero-width para posicionar el cursor
                            const span = document.createElement('span');
                            span.dataset.colorClass = color;
                            span.className = 'fi-fo-wysiwyg-color-' + color;
                            span.innerHTML = '\u200B'; // Zero-width space

                            range.insertNode(span);

                            // Posicionar el cursor dentro del span
                            const newRange = document.createRange();
                            newRange.selectNodeContents(span);
                            newRange.collapse(false);
                            selection.removeAllRanges();
                            selection.addRange(newRange);
                        } else {
                            // Quitar color: verificar si estamos dentro de un span de color y salir
                            let container = range.startContainer;
                            if (container.nodeType === Node.TEXT_NODE) {
                                container = container.parentElement;
                            }

                            // Buscar si estamos dentro de un span con color
                            const colorSpan = container?.closest('span[data-color-class]');
                            if (colorSpan && colorSpan !== this.$refs.editor) {
                                // Posicionar el cursor después del span de color
                                const newRange = document.createRange();
                                newRange.setStartAfter(colorSpan);
                                newRange.collapse(true);

                                // Insertar un espacio para poder escribir
                                const textNode = document.createTextNode('\u00A0');
                                newRange.insertNode(textNode);

                                // Posicionar cursor después del espacio
                                newRange.setStartAfter(textNode);
                                newRange.collapse(true);
                                selection.removeAllRanges();
                                selection.addRange(newRange);
                            }
                        }
                    } else {
                        // Hay selección: aplicar o quitar color al texto seleccionado
                        if (!color) {
                            // Quitar color: buscar spans con color en la selección y desenvolverlos
                            let container = range.commonAncestorContainer;
                            if (container.nodeType === Node.TEXT_NODE) {
                                container = container.parentElement;
                            }

                            // Buscar el span de color más cercano
                            const colorSpan = container?.closest('span[data-color-class]');
                            if (colorSpan && colorSpan !== this.$refs.editor) {
                                // Desenvolver el span: reemplazar con su contenido
                                const parent = colorSpan.parentNode;
                                while (colorSpan.firstChild) {
                                    parent.insertBefore(colorSpan.firstChild, colorSpan);
                                }
                                parent.removeChild(colorSpan);
                            } else {
                                // Si no hay span de color, simplemente reinsertamos el contenido
                                const selectedContent = range.extractContents();
                                range.insertNode(selectedContent);
                            }
                        } else {
                            // Aplicar color: extraer contenido y envolverlo en span
                            const selectedContent = range.extractContents();

                            // Crear span con color
                            const span = document.createElement('span');
                            span.dataset.colorClass = color;
                            span.className = 'fi-fo-wysiwyg-color-' + color;
                            span.appendChild(selectedContent);
                            range.insertNode(span);

                            // Seleccionar el nuevo span
                            selection.selectAllChildren(span);
                        }
                    }
                }
                this.currentTextColor = color;
                this.updateState();
            },

            // Convertir HTML del editor a formato JSON de ClickUp
            htmlToClickUpJson(html) {
                if (!html || html.trim() === '') {
                    return { comment: [] };
                }

                try {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString('<div>' + html + '</div>', 'text/html');
                    const container = doc.body.firstChild;
                    const result = [];

                    this.processHtmlNodeToJson(container, result, []);

                    return { comment: result };
                } catch (e) {
                    console.error('Error converting HTML to JSON:', e);
                    return { comment: [] };
                }
            },

            // Procesar nodo HTML y convertirlo a formato JSON de ClickUp
            processHtmlNodeToJson(node, result, context) {
                if (!node) return;

                if (node.nodeType === Node.TEXT_NODE) {
                    const text = node.textContent;

                    // Si estamos en una lista o bloque de código, el texto puede contener saltos de línea
                    // que deben ser tratados especialmente en ClickUp
                    if (context.list || context.codeBlock) {
                        const lines = text.split('\n');
                        lines.forEach((line, index) => {
                            if (line !== '') {
                                result.push({
                                    text: line,
                                    attributes: this.buildAttributesFromContext(context, false)
                                });
                            }

                            // Si no es el último fragmento, significa que había un \n
                            // (excepto si el texto termina en \n, en cuyo caso lines[last] es '')
                            if (index < lines.length - 1) {
                                result.push({
                                    text: '\n',
                                    attributes: this.buildAttributesFromContext(context, true)
                                });
                            }
                        });
                    } else if (text.trim() !== '' || text === '\n') {
                        result.push({
                            text: text,
                            attributes: this.buildAttributesFromContext(context, false)
                        });
                    }
                    return;
                }

                if (node.nodeType === Node.ELEMENT_NODE) {
                    const tagName = node.tagName.toLowerCase();
                    const newContext = { ...context };

                    // Detectar menciones (elementos con data-mention-id)
                    if (node.hasAttribute && node.hasAttribute('data-mention-id')) {
                        const mentionId = node.getAttribute('data-mention-id');
                        const mentionLabel = node.getAttribute('data-mention-label') || '';

                        if (mentionId) {
                            result.push({
                                type: 'tag',
                                user: {
                                    id: parseInt(mentionId, 10),
                                    username: mentionLabel, // Guardamos el nombre para ClickUp
                                    name: mentionLabel     // Guardamos el nombre para nuestra vista
                                }
                            });
                            return;
                        }
                    }

                    // Actualizar contexto según el tag
                    switch (tagName) {
                        case 'strong':
                        case 'b':
                            newContext.bold = true;
                            break;
                        case 'em':
                        case 'i':
                            newContext.italic = true;
                            break;
                        case 'u':
                            newContext.underline = true;
                            break;
                        case 's':
                        case 'strike':
                        case 'del':
                            newContext.strike = true;
                            break;
                        case 'code':
                            newContext.code = true;
                            break;
                        case 'pre':
                            newContext.codeBlock = 'plain';
                            break;
                        case 'h1':
                            newContext.header = 1;
                            break;
                        case 'h2':
                            newContext.header = 2;
                            break;
                        case 'h3':
                            newContext.header = 3;
                            break;
                        case 'blockquote':
                            newContext.blockquote = true;
                            break;
                        case 'span':
                            // Detectar color
                            if (node.dataset && node.dataset.colorClass) {
                                newContext.colorClass = node.dataset.colorClass;
                            }
                            break;
                        case 'hr':
                            result.push({
                                text: '---',
                                type: 'divider'
                            });
                            return;
                        case 'a':
                            const href = node.getAttribute('href');
                            if (href) {
                                newContext.link = href;
                            }
                            break;
                        case 'ul':
                            if (node.classList.contains('checklist')) {
                                newContext.list = 'unchecked'; // Valor por defecto
                            } else {
                                newContext.list = 'bullet';
                            }
                            break;
                        case 'ol':
                            newContext.list = 'ordered';
                            break;
                        case 'li':
                            if (node.hasAttribute('data-checked')) {
                                newContext.list = node.getAttribute('data-checked') === 'true' ? 'checked' : 'unchecked';
                            }
                            break;
                        case 'br':
                            result.push({
                                text: '\n',
                                attributes: this.buildAttributesFromContext(context, true)
                            });
                            return;
                        case 'p':
                        case 'div':
                            if (result.length > 0) {
                                const lastItem = result[result.length - 1];
                                if (!lastItem.text || !lastItem.text.endsWith('\n')) {
                                    result.push({
                                        text: '\n',
                                        attributes: this.buildAttributesFromContext(context, false)
                                    });
                                }
                            }
                            break;
                    }

                    // Procesar hijos
                    Array.from(node.childNodes).forEach(child => {
                        this.processHtmlNodeToJson(child, result, newContext);
                    });

                    // Después de listItem, agregar salto de línea con atributo de lista
                    if (tagName === 'li' && newContext.list) {
                        result.push({
                            text: '\n',
                            attributes: {
                                list: { list: newContext.list }
                            }
                        });
                    }

                    // Después de pre, agregar salto de línea con atributo de bloque de código
                    if (tagName === 'pre' && newContext.codeBlock) {
                        result.push({
                            text: '\n',
                            attributes: {
                                'code-block': { 'code-block': newContext.codeBlock }
                            }
                        });
                    }

                    // Después de h1, h2, h3, agregar salto de línea con atributo header
                    if ((tagName === 'h1' || tagName === 'h2' || tagName === 'h3') && newContext.header) {
                        result.push({
                            text: '\n',
                            attributes: {
                                header: newContext.header
                            }
                        });
                    }

                    // Después de blockquote, agregar salto de línea con atributo blockquote
                    if (tagName === 'blockquote' && newContext.blockquote) {
                        result.push({
                            text: '\n',
                            attributes: {
                                blockquote: { 'blockquote-id': 'quote-' + Math.random().toString(36).substr(2, 6) }
                            }
                        });
                    }

                    // Después de p o div, agregar salto de línea si no es el último
                    if ((tagName === 'p' || tagName === 'div') && node.nextSibling !== null) {
                        result.push({
                            text: '\n',
                            attributes: this.buildAttributesFromContext(context, false)
                        });
                    }
                }
            },

            // Construir atributos desde el contexto
            buildAttributesFromContext(context, includeExtras = false) {
                const attributes = {};

                if (context.bold) attributes.bold = true;
                if (context.italic) attributes.italic = true;
                if (context.underline) attributes.underline = true;
                if (context.strike) attributes.strike = true;
                if (context.code) attributes.code = true;
                if (context.link) attributes.link = context.link;
                if (context.colorClass) attributes['color-class'] = context.colorClass;

                // En ClickUp, solo los saltos de línea (\n) al final llevan atributos de lista, bloque de código, header o blockquote
                if (includeExtras) {
                    if (context.list) attributes.list = { list: context.list };
                    if (context.codeBlock) attributes['code-block'] = { 'code-block': context.codeBlock };
                    if (context.header) attributes.header = context.header;
                    if (context.blockquote) attributes.blockquote = { 'blockquote-id': 'quote-' + Math.random().toString(36).substr(2, 6) };
                }

                return attributes;
            },

            // Convertir JSON de ClickUp a HTML para mostrar en el editor
            clickUpJsonToHtml(commentArray) {
                if (!Array.isArray(commentArray) || commentArray.length === 0) {
                    return '';
                }

                let html = '';
                let currentListType = null;
                let listOpen = false;
                let lineBuffer = '';

                // Función para volcar el búfer de línea al HTML final
                const flushLine = (attrs) => {
                    const listType = attrs && attrs.list ? attrs.list.list : null;
                    const codeBlockType = attrs && attrs['code-block'] ? attrs['code-block']['code-block'] : null;
                    const headerLevel = attrs && attrs.header ? attrs.header : null;
                    const isBlockquote = attrs && attrs.blockquote;

                    // Cerrar lista si estamos saliendo de ella
                    if (listOpen && !listType) {
                        html += (currentListType === 'bullet' || currentListType === 'checked' || currentListType === 'unchecked') ? '</ul>' : '</ol>';
                        listOpen = false;
                        currentListType = null;
                    }

                    if (listType) {
                        if (listType !== currentListType) {
                            if (listOpen) {
                                html += (currentListType === 'bullet' || currentListType === 'checked' || currentListType === 'unchecked') ? '</ul>' : '</ol>';
                            }
                            html += (listType === 'bullet') ? '<ul>' : (listType === 'ordered' ? '<ol>' : '<ul class=\'checklist\'>');
                            listOpen = true;
                            currentListType = listType;
                        }
                        const isCheckedAttr = (listType === 'checked' || listType === 'unchecked') ? ' data-checked=\'' + (listType === 'checked' ? 'true' : 'false') + '\'' : '';
                        html += '<li' + isCheckedAttr + '>' + (lineBuffer || ' ') + '</li>';
                    } else if (codeBlockType) {
                        html += '<pre class=\'fi-fo-wysiwyg-code-block\'>' + (lineBuffer || ' ') + '</pre>';
                    } else if (headerLevel) {
                        html += '<h' + headerLevel + '>' + (lineBuffer || ' ') + '</h' + headerLevel + '>';
                    } else if (isBlockquote) {
                        html += '<blockquote class=\'fi-fo-wysiwyg-blockquote\'>' + (lineBuffer || ' ') + '</blockquote>';
                    } else {
                        html += lineBuffer + '<br>';
                    }
                    lineBuffer = '';
                };

                commentArray.forEach((item, index) => {
                    // Manejar menciones (tags)
                    if (item.type === 'tag' && item.user && item.user.id) {
                        let label = item.user.name || item.user.username;
                        if (!label) {
                            const mention = this.mentions.find(m => String(m.id) === String(item.user.id));
                            label = mention ? mention.label : 'Usuario';
                        }
                        lineBuffer += '<span data-mention-id=\'' + item.user.id + '\' data-mention-label=\'' + this.escapeHtml(label) + '\' class=\'mention-tag\' contenteditable=\'false\'>@' + this.escapeHtml(label) + '</span>';
                        return;
                    }

                    // Manejar separadores (divider)
                    if (item.type === 'divider') {
                        if (lineBuffer) flushLine(null);
                        html += '<hr class=\'fi-fo-wysiwyg-divider\'>';
                        return;
                    }

                    // Manejar archivos adjuntos (attachments)
                    if (item.type === 'attachment' && item.attachment) {
                        if (lineBuffer) flushLine(null);
                        const att = item.attachment;
                        const url = att.url || att.url_w_host || '#';
                        const title = this.escapeHtml(att.title || item.text || 'Archivo adjunto');
                        const size = att.size ? this.formatFileSize(att.size) : '';
                        const extension = att.extension || '';

                        html += '<div class=\'fi-fo-wysiwyg-attachment\' data-attachment-id=\'' + (att.id || '') + '\'>' +
                                '<a href=\'' + this.escapeHtml(url) + '\' target=\'_blank\' class=\'fi-fo-wysiwyg-attachment-link\'>' +
                                '<svg class=\'fi-fo-wysiwyg-attachment-icon\' xmlns=\'http://www.w3.org/2000/svg\' width=\'20\' height=\'20\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48\'/></svg>' +
                                '<span class=\'fi-fo-wysiwyg-attachment-name\'>' + title + '</span>' +
                                (size ? '<span class=\'fi-fo-wysiwyg-attachment-size\'>' + size + '</span>' : '') +
                                '</a>' +
                                '</div>';
                        return;
                    }

                    // Manejar imágenes
                    if (item.type === 'image' && item.image) {
                        if (lineBuffer) flushLine(null);
                        const img = item.image;
                        const url = img.url || img.thumbnail_large || img.thumbnail_medium || '#';
                        const title = this.escapeHtml(img.title || img.name || 'Imagen');
                        const width = item.attributes && item.attributes.width ? item.attributes.width : '300';

                        html += '<div class=\'fi-fo-wysiwyg-image-container\'>' +
                                '<img src=\'' + this.escapeHtml(url) + '\' alt=\'' + title + '\' class=\'fi-fo-wysiwyg-image\' style=\'max-width: ' + width + 'px;\' data-image-id=\'' + (img.id || '') + '\'>' +
                                '</div>';
                        return;
                    }

                    // Manejar botones
                    if (item.type === 'button' && item.button) {
                        if (lineBuffer) flushLine(null);
                        const btn = item.button;
                        const url = btn.url || '#';
                        const title = this.escapeHtml(btn.title || item.text || 'Botón');
                        const align = btn.align || 'left';
                        const bgColor = btn.backgroundColor || item.backgroundColor || '';
                        const textColor = btn.color || item.color || '';

                        let alignClass = 'fi-fo-wysiwyg-button-left';
                        if (align === 'center') alignClass = 'fi-fo-wysiwyg-button-center';
                        if (align === 'right') alignClass = 'fi-fo-wysiwyg-button-right';

                        let buttonStyle = '';
                        if (bgColor) buttonStyle += 'background-color: ' + bgColor + ';';
                        if (textColor) buttonStyle += 'color: ' + textColor + ';';

                        html += '<div class=\'fi-fo-wysiwyg-button-container ' + alignClass + '\'>' +
                                '<a href=\'' + this.escapeHtml(url) + '\' target=\'_blank\' class=\'fi-fo-wysiwyg-button\'' + (buttonStyle ? ' style=\'' + buttonStyle + '\'' : '') + '>' +
                                title +
                                '</a>' +
                                '</div>';
                        return;
                    }

                    const text = item.text || '';
                    const attrs = item.attributes || {};

                    // Si es un salto de línea, procesamos la línea acumulada
                    if (text === '\n') {
                        flushLine(attrs);
                        return;
                    }

                    // Construir el texto con formato
                    let formattedText = this.escapeHtml(text);

                    // Aplicar formato
                    if (attrs.bold) formattedText = '<strong>' + formattedText + '</strong>';
                    if (attrs.italic) formattedText = '<em>' + formattedText + '</em>';
                    if (attrs.underline) formattedText = '<u>' + formattedText + '</u>';
                    if (attrs.strike) formattedText = '<s>' + formattedText + '</s>';
                    if (attrs.code) formattedText = '<code>' + formattedText + '</code>';
                    if (attrs.link) formattedText = '<a href=\'' + this.escapeHtml(attrs.link) + '\'>' + formattedText + '</a>';
                    if (attrs['color-class']) formattedText = '<span data-color-class=\'' + attrs['color-class'] + '\' class=\'fi-fo-wysiwyg-color-' + attrs['color-class'] + '\'>' + formattedText + '</span>';

                    lineBuffer += formattedText;
                });

                // Si quedó algo en el búfer sin un \n final
                if (lineBuffer) {
                    flushLine(null);
                }

                // Cerrar lista si quedó abierta
                if (listOpen) {
                    if (currentListType === 'bullet' || currentListType === 'checked' || currentListType === 'unchecked') {
                        html += '</ul>';
                    } else {
                        html += '</ol>';
                    }
                }

                return html;
            },

            // Escapar HTML para seguridad
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            },

            // Formatear tamaño de archivo
            formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
            },

            get htmlPreview() {
                // Mostrar el JSON formateado del estado actual
                let jsonToShow = this.state;

                // Si el estado ya tiene el formato correcto, mostrarlo
                if (typeof jsonToShow === 'object' && jsonToShow !== null) {
                    // Si tiene formato {comment: [...]}, mostrarlo directamente
                    if (jsonToShow.comment) {
                        return JSON.stringify(jsonToShow, null, 2);
                    }
                    // Si es array, envolverlo en el formato correcto
                    if (Array.isArray(jsonToShow)) {
                        return JSON.stringify({ comment: jsonToShow }, null, 2);
                    }
                }

                return JSON.stringify({ comment: [] }, null, 2);
            },

            toggleHtmlPreview() {
                this.showHtmlPreview = !this.showHtmlPreview;
            },

            formatText(command) {
                if (this.isDisabled || !this.$refs.editor) return;
                this.$refs.editor.focus();

                if (command === 'code') {
                    const selection = window.getSelection();
                    if (selection.rangeCount > 0) {
                        const range = selection.getRangeAt(0);

                        if (range.collapsed) {
                            // Si no hay selección, intentamos activar el estilo para lo que se escriba después
                            // Pero con execCommand('code') no funciona, así que insertamos un code vacío
                            const code = document.createElement('code');
                            code.innerHTML = '&#8203;'; // Zero width space
                            range.insertNode(code);
                            range.setStart(code.firstChild, 1);
                            range.collapse(true);
                            selection.removeAllRanges();
                            selection.addRange(range);
                        } else {
                            // Si hay selección, comprobamos si ya está envuelta en code
                            let container = range.commonAncestorContainer;
                            if (container.nodeType === Node.TEXT_NODE) container = container.parentNode;

                            if (container.tagName === 'CODE' && container.closest('.fi-fo-wysiwyg-content')) {
                                // Desenvolver (quitar tag code)
                                const text = document.createTextNode(container.textContent);
                                container.parentNode.replaceChild(text, container);
                            } else {
                                // Envolver selección en code
                                const code = document.createElement('code');
                                code.appendChild(range.extractContents());
                                range.insertNode(code);
                                selection.selectAllChildren(code);
                            }
                        }
                    }
                } else {
                    document.execCommand(command, false, null);
                }

                this.updateState();
            },

            insertLink() {
                if (this.isDisabled || !this.$refs.editor) return;
                const url = prompt(@js(__('filament-wysiwyg-json-formatter::wysiwyg.prompts.enter_url')));
                if (url) {
                    this.$refs.editor.focus();
                    document.execCommand('createLink', false, url);
                    this.updateState();
                }
            },

            handleKeydown(event) {
                if (this.isDisabled) return;
                if (this.hasMentions && (event.key === '@' || (event.shiftKey && event.key === '2'))) {
                    setTimeout(() => {
                        this.showMentionsPanel = true;
                        this.mentionSearch = '';
                        this.selectedMentionIndex = 0;
                    }, 10);
                } else if (this.showMentionsPanel) {
                    // Manejar navegación con flechas, Enter y Tab
                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        const maxIndex = this.filteredMentions.length - 1;
                        this.selectedMentionIndex = Math.min(this.selectedMentionIndex + 1, Math.max(0, maxIndex));
                        return;
                    } else if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        this.selectedMentionIndex = Math.max(this.selectedMentionIndex - 1, 0);
                        return;
                    } else if ((event.key === 'Enter' || event.key === 'Tab') && this.filteredMentions.length > 0) {
                        event.preventDefault();
                        // Asegurar que el índice está dentro del rango
                        const safeIndex = Math.min(this.selectedMentionIndex, this.filteredMentions.length - 1);
                        const selectedMention = this.filteredMentions[safeIndex];
                        if (selectedMention) {
                            this.insertMention(selectedMention.id, selectedMention.label);
                        }
                        return;
                    }

                    // Actualizar la búsqueda mientras escriben
                    if (event.key.length === 1 && !event.ctrlKey && !event.metaKey) {
                        setTimeout(() => {
                            const selection = window.getSelection();
                            if (selection.rangeCount > 0) {
                                const range = selection.getRangeAt(0);
                                const textNode = range.startContainer;

                                if (textNode.nodeType === Node.TEXT_NODE) {
                                    const text = textNode.textContent;
                                    const cursorPos = range.startOffset;

                                    // Buscar el último @ antes del cursor (sin limitar por espacios)
                                    let atPos = -1;
                                    for (let i = cursorPos - 1; i >= 0; i--) {
                                        if (text[i] === '@') {
                                            atPos = i;
                                            break;
                                        }
                                        // Solo romper si encuentra un salto de línea
                                        if (text[i] === '\n') {
                                            break;
                                        }
                                    }

                                    // Actualizar el término de búsqueda (ahora permite espacios)
                                    if (atPos !== -1) {
                                        this.mentionSearch = text.substring(atPos + 1, cursorPos);
                                        this.selectedMentionIndex = 0;
                                    }
                                }
                            }
                        }, 10);
                    } else if (event.key === 'Backspace') {
                        setTimeout(() => {
                            const selection = window.getSelection();
                            if (selection.rangeCount > 0) {
                                const range = selection.getRangeAt(0);
                                const textNode = range.startContainer;

                                if (textNode.nodeType === Node.TEXT_NODE) {
                                    const text = textNode.textContent;
                                    const cursorPos = range.startOffset;

                                    // Buscar el último @ antes del cursor (sin limitar por espacios)
                                    let atPos = -1;
                                    for (let i = cursorPos - 1; i >= 0; i--) {
                                        if (text[i] === '@') {
                                            atPos = i;
                                            break;
                                        }
                                        // Solo romper si encuentra un salto de línea
                                        if (text[i] === '\n') {
                                            break;
                                        }
                                    }

                                    if (atPos !== -1) {
                                        this.mentionSearch = text.substring(atPos + 1, cursorPos);
                                        this.selectedMentionIndex = 0;
                                    } else {
                                        // Solo cerrar si no hay @ o ya no hay búsqueda
                                        this.showMentionsPanel = false;
                                    }
                                }
                            }
                        }, 10);
                    } else if (event.key === 'Escape') {
                        this.showMentionsPanel = false;
                        this.mentionSearch = '';
                        this.selectedMentionIndex = 0;
                    }
                }
            },

            toggleMentionsPanel() {
                this.showMentionsPanel = !this.showMentionsPanel;
                if (this.showMentionsPanel) {
                    this.selectedMentionIndex = 0;
                }
            },

            get filteredMentions() {
                if (!this.mentionSearch) {
                    return this.mentions;
                }
                // Hacer trim para eliminar espacios al inicio y final, pero mantener espacios internos
                const search = this.mentionSearch.trim().toLowerCase();

                // Si después del trim no hay búsqueda, mostrar todos
                if (!search) {
                    return this.mentions;
                }

                return this.mentions.filter(mention => {
                    const label = mention ? String(mention.label || '').toLowerCase() : '';
                    return label.includes(search);
                });
            },

            insertMention(id, label) {
                if (this.isDisabled || !this.$refs.editor) return;

                const editor = this.$refs.editor;
                editor.focus();

                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    const range = selection.getRangeAt(0);

                    // Buscar y eliminar el @ y el texto escrito antes
                    const textNode = range.startContainer;
                    if (textNode.nodeType === Node.TEXT_NODE) {
                        const text = textNode.textContent;
                        const cursorPos = range.startOffset;

                        // Buscar el último @ antes del cursor (permitiendo espacios)
                        let atPos = -1;
                        for (let i = cursorPos - 1; i >= 0; i--) {
                            if (text[i] === '@') {
                                atPos = i;
                                break;
                            }
                            // Solo romper si encuentra un salto de línea
                            if (text[i] === '\n') {
                                break;
                            }
                        }

                        // Si encontramos un @, eliminar desde ahí hasta el cursor
                        if (atPos !== -1) {
                            range.setStart(textNode, atPos);
                            range.setEnd(textNode, cursorPos);
                            range.deleteContents();
                        }
                    }

                    // Crear un elemento span especial para la mención
                    const mentionSpan = document.createElement('span');
                    mentionSpan.setAttribute('data-mention-id', id);
                    mentionSpan.setAttribute('data-mention-label', label);
                    mentionSpan.className = 'mention-tag';
                    mentionSpan.textContent = '@' + label;
                    mentionSpan.contentEditable = 'false';

                    // Insertar espacios después de la mención (dos espacios para asegurar separación)
                    const spaceNode = document.createTextNode('  ');

                    // Insertar mención primero, luego espacio
                    range.insertNode(mentionSpan);

                    // Posicionar después del span para insertar el espacio
                    range.setStartAfter(mentionSpan);
                    range.collapse(true);
                    range.insertNode(spaceNode);

                    // Posicionar el cursor después del espacio
                    range.setStartAfter(spaceNode);
                    range.collapse(true);
                    selection.removeAllRanges();
                    selection.addRange(range);
                } else {
                    // Fallback: agregar al final del editor
                    const mentionSpan = document.createElement('span');
                    mentionSpan.setAttribute('data-mention-id', id);
                    mentionSpan.setAttribute('data-mention-label', label);
                    mentionSpan.className = 'mention-tag';
                    mentionSpan.textContent = '@' + label;
                    mentionSpan.contentEditable = 'false';

                    editor.appendChild(mentionSpan);
                    editor.appendChild(document.createTextNode('  '));

                    // Mover cursor al final
                    const newRange = document.createRange();
                    newRange.selectNodeContents(editor);
                    newRange.collapse(false);
                    selection.removeAllRanges();
                    selection.addRange(newRange);
                }

                // Cerrar panel y limpiar búsqueda primero
                this.showMentionsPanel = false;
                this.mentionSearch = '';

                // Actualizar estado
                this.updateState();
            }
        }"
        wire:ignore
        class="fi-fo-wysiwyg-editor-container"
        x-on:selectionchange.document="updateActiveStyles()"
    >
        {{-- Toolbar --}}
        @if (! $isDisabled && ! empty($toolbarButtons))
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
                        x-on:click.prevent="formatText('strikeThrough')"
                        :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.strike')"
                        tabindex="-1"
                    />
                @endif

                @if (in_array('code', $toolbarButtons))
                    <x-filament::icon-button
                        icon="lucide-code-2"
                        size="sm"
                        x-bind:color="activeStyles.code ? 'primary' : 'gray'"
                        x-on:click.prevent="formatText('code')"
                        :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.code')"
                        tabindex="-1"
                    />
                @endif

                @if (in_array('codeBlock', $toolbarButtons))
                    <x-filament::icon-button
                        icon="lucide-square-code"
                        size="sm"
                        x-bind:color="activeStyles.codeBlock ? 'primary' : 'gray'"
                        x-on:click.prevent="toggleCodeBlock()"
                        :tooltip="__('filament-wysiwyg-json-formatter::wysiwyg.toolbar.code_block')"
                        tabindex="-1"
                    />
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
                @endif

                @if ($hasMentions())
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
        @endif

        {{-- Editor --}}
        <div
            x-ref="editor"
            wire:ignore.self
            contenteditable="true"
            @input="updateState()"
            @keydown="handleKeydown($event)"
            class="fi-fo-wysiwyg-content"
            :class="{ 'opacity-50 cursor-not-allowed': isDisabled }"
            :contenteditable="!isDisabled"
        ></div>

        {{-- JSON Preview --}}
        <div
            x-show="showHtmlPreview"
            x-cloak
            class="fi-fo-wysiwyg-html-preview"
        >
            <div class="fi-fo-wysiwyg-html-preview-header">
                <span class="text-sm font-medium">{{ __('filament-wysiwyg-json-formatter::wysiwyg.json_preview.title') }}</span>
                <button
                    type="button"
                    @click.prevent="showHtmlPreview = false"
                    class="fi-fo-wysiwyg-html-preview-close"
                    tabindex="-1"
                    title="{{ __('filament-wysiwyg-json-formatter::wysiwyg.json_preview.close') }}"
                >
                    ×
                </button>
            </div>
            <pre class="fi-fo-wysiwyg-html-preview-content" x-text="htmlPreview"></pre>
        </div>

        {{-- Mentions Panel --}}
        @if ($hasMentions())
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
                        title="{{ __('filament-wysiwyg-json-formatter::wysiwyg.mentions.close') }}"
                    >
                        ×
                    </button>
                </div>

                <div class="p-2">
                    <input
                        type="text"
                        x-model="mentionSearch"
                        placeholder="Buscar..."
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
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

                    <div x-show="filteredMentions.length === 0" class="p-3 text-sm text-gray-500 text-center">
                        {{ __('filament-wysiwyg-json-formatter::wysiwyg.mentions.no_users_found') }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-dynamic-component>
