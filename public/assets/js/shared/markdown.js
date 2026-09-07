// ============================================
// MINIMAL MARKDOWN RENDERER (shared)
// Supports just the subset the job-posting Description toolbar can
// produce: # / ## / ### headers, **bold**, *italic*, ~~strikethrough~~,
// `code`, [text](url) links, and - / 1. lists. Deliberately not a full
// CommonMark implementation -- this only needs to round-trip what the
// toolbar in job_postings.php writes, and it's rendered on a public page
// (the Apply form's job detail panel) so every raw HTML tag from the
// input is escaped BEFORE any markdown substitution runs.
// ============================================
(function (global) {
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = String(text == null ? '' : text);
        return div.innerHTML;
    }

    function inline(text) {
        return text
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
            .replace(/~~([^~]+)~~/g, '<s>$1</s>')
            .replace(/(?<!\*)\*([^*]+)\*(?!\*)/g, '<em>$1</em>')
            .replace(/_([^_]+)_/g, '<em>$1</em>')
            .replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');
    }

    function mdToHtml(raw) {
        const text = escapeHtml(raw || '').replace(/\r\n/g, '\n');
        const lines = text.split('\n');
        const out = [];
        let listBuffer = [];
        let listType = null;

        function flushList() {
            if (!listBuffer.length) return;
            const tag = listType === 'ol' ? 'ol' : 'ul';
            out.push(`<${tag}>` + listBuffer.map(li => `<li>${inline(li)}</li>`).join('') + `</${tag}>`);
            listBuffer = [];
            listType = null;
        }

        let paraBuffer = [];
        function flushPara() {
            if (!paraBuffer.length) return;
            out.push('<p>' + paraBuffer.map(inline).join('<br>') + '</p>');
            paraBuffer = [];
        }

        lines.forEach(line => {
            const trimmed = line.trim();

            const heading = trimmed.match(/^(#{1,3})\s+(.*)$/);
            if (heading) {
                flushPara(); flushList();
                const level = heading[1].length;
                out.push(`<h${level}>${inline(heading[2])}</h${level}>`);
                return;
            }

            const bullet = trimmed.match(/^[-*]\s+(.*)$/);
            if (bullet) {
                flushPara();
                if (listType !== 'ul') { flushList(); listType = 'ul'; }
                listBuffer.push(bullet[1]);
                return;
            }

            const numbered = trimmed.match(/^\d+\.\s+(.*)$/);
            if (numbered) {
                flushPara();
                if (listType !== 'ol') { flushList(); listType = 'ol'; }
                listBuffer.push(numbered[1]);
                return;
            }

            flushList();
            if (trimmed === '') {
                flushPara();
            } else {
                paraBuffer.push(trimmed);
            }
        });
        flushList();
        flushPara();

        return out.join('');
    }

    global.mdToHtml = mdToHtml;
})(window);
