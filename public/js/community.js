(() => {
    'use strict';
    const $ = (s, root = document) => root.querySelector(s);
    const $$ = (s, root = document) => [...root.querySelectorAll(s)];
    const storage = {
        get(key, fallback) { try { return JSON.parse(localStorage.getItem(`syncode:${key}`)) ?? fallback; } catch { return fallback; } },
        set(key, value) { try { localStorage.setItem(`syncode:${key}`, JSON.stringify(value)); return true; } catch { toast('Browser storage is unavailable. Changes will last for this visit.'); return false; } }
    };
    let toastTimer;
    function toast(message) { const el = $('.toast'); el.textContent = message; el.hidden = false; clearTimeout(toastTimer); toastTimer = setTimeout(() => el.hidden = true, 4500); }
    const escapeHtml = text => text.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    function markdown(value) {
        return value.split('\n').map(line => {
            let text = escapeHtml(line).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').replace(/\*(.+?)\*/g, '<em>$1</em>').replace(/`(.+?)`/g, '<code>$1</code>');
            if (text.startsWith('## ')) return `<h2>${text.slice(3)}</h2>`;
            if (text.startsWith('# ')) return `<h2>${text.slice(2)}</h2>`;
            return text ? `<p>${text}</p>` : '<br>';
        }).join('');
    }
    $('[data-menu]')?.addEventListener('click', e => { const open = $('#navigation').classList.toggle('open'); e.currentTarget.setAttribute('aria-expanded', open); });
    document.addEventListener('keydown', e => { if (e.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) { const input = $('.global-search input'); if (input && getComputedStyle(input.closest('form')).display !== 'none') { e.preventDefault(); input.focus(); } } if (e.key === 'Escape') { $('#navigation').classList.remove('open'); $('[data-menu]')?.setAttribute('aria-expanded', 'false'); } });
    let bookmarks = storage.get('bookmarks', []), likes = storage.get('likes', []), following = storage.get('following', []);
    const prefs = storage.get('preferences', {});
    const applyPrefs = () => { document.body.classList.toggle('comfortable', !!prefs.comfortable); document.body.classList.toggle('reduce-motion', !!prefs.reduceMotion); };
    applyPrefs();
    function syncButtons() {
        $$('[data-bookmark]').forEach(b => b.setAttribute('aria-pressed', bookmarks.includes(b.dataset.bookmark)));
        $$('[data-like]').forEach(b => { const active = likes.includes(b.dataset.like); b.setAttribute('aria-pressed', active); const count = $('span', b); if (count) { b.dataset.baseCount ??= count.textContent; count.textContent = Number(b.dataset.baseCount) + Number(active); } });
        $$('[data-follow]').forEach(b => { const active = following.includes(b.dataset.follow); b.setAttribute('aria-pressed', active); if (b.classList.contains('follow-button')) { b.textContent = active ? '✓' : '+'; b.setAttribute('aria-label', `${active ? 'Unfollow' : 'Follow'} ${b.dataset.follow}`); } else b.textContent = active ? 'Following ✓' : 'Follow'; });
    }
    function toggle(list, value) { return list.includes(value) ? list.filter(x => x !== value) : [...list, value]; }
    $$('[data-bookmark]').forEach(b => b.addEventListener('click', () => { bookmarks = toggle(bookmarks, b.dataset.bookmark); storage.set('bookmarks', bookmarks); syncButtons(); filterPosts(); toast(bookmarks.includes(b.dataset.bookmark) ? 'Story added to your reading corner.' : 'Story removed from bookmarks.'); }));
    $$('[data-like]').forEach(b => b.addEventListener('click', () => { likes = toggle(likes, b.dataset.like); storage.set('likes', likes); syncButtons(); }));
    $$('[data-follow]').forEach(b => b.addEventListener('click', () => { following = toggle(following, b.dataset.follow); storage.set('following', following); syncButtons(); filterPosts(); toast(following.includes(b.dataset.follow) ? `You’re following ${b.dataset.follow} in this preview.` : 'Author removed from your following feed.'); }));
    syncButtons();
    let currentTopic = new URLSearchParams(location.search).get('topic') || '';
    let currentTab = 'all';
    let search = new URLSearchParams(location.search).get('q') || '';
    const cards = $$('[data-post]');
    cards.forEach((card, i) => card.dataset.originalOrder = i);
    function filterPosts() {
        if (!cards.length) return;
        let visible = 0;
        cards.forEach(card => { const show = (document.body.dataset.page !== 'bookmarks' || bookmarks.includes(card.dataset.slug)) && (!currentTopic || card.dataset.category === currentTopic) && (!search || card.textContent.toLowerCase().includes(search.toLowerCase())) && (currentTab !== 'following' || following.includes(card.dataset.author)); card.hidden = !show; visible += Number(show); });
        const empty = $('[data-empty]'); if (empty) empty.hidden = visible > 0;
        if ($('.feed-end')) $('.feed-end').hidden = visible === 0;
    }
    $$('[data-topic]').forEach(b => { b.classList.toggle('selected', b.dataset.topic === currentTopic); b.setAttribute('aria-pressed', b.dataset.topic === currentTopic); b.addEventListener('click', () => { currentTopic = b.dataset.topic; $$('[data-topic]').forEach(x => { x.classList.toggle('selected', x === b); x.setAttribute('aria-pressed', x === b); }); filterPosts(); }); });
    $$('[data-tab]').forEach(b => { b.setAttribute('aria-pressed', b.classList.contains('selected')); b.addEventListener('click', () => { currentTab = b.dataset.tab; $$('[data-tab]').forEach(x => { x.classList.toggle('selected', x === b); x.setAttribute('aria-pressed', x === b); }); const parent = $('#post-list'); if (parent) [...cards].sort((a, c) => currentTab === 'trending' ? Number(c.dataset.likes) - Number(a.dataset.likes) : Number(a.dataset.originalOrder) - Number(c.dataset.originalOrder)).forEach(card => parent.appendChild(card)); filterPosts(); }); });
    if (document.body.dataset.page === 'feed') { $('.global-search')?.addEventListener('submit', e => { e.preventDefault(); search = $('input', e.currentTarget).value.trim(); filterPosts(); }); $('.global-search input')?.addEventListener('input', e => { search = e.target.value.trim(); filterPosts(); }); }
    $('.mobile-feed-search')?.addEventListener('submit', e => e.preventDefault());
    $('.mobile-feed-search input')?.addEventListener('input', e => { search = e.target.value.trim(); if ($('.global-search input')) $('.global-search input').value = search; filterPosts(); });
    const draftTitle = storage.get('draft', {}).title;
    if ($('#local-draft-title') && draftTitle) $('#local-draft-title').textContent = draftTitle;
    $$('[data-story-status]').forEach(b => b.addEventListener('click', () => { $$('[data-story-status]').forEach(x => { x.classList.toggle('selected', x === b); x.setAttribute('aria-pressed', x === b); }); $$('[data-my-story]').forEach(row => row.hidden = !!b.dataset.storyStatus && row.dataset.myStory !== b.dataset.storyStatus); }));
    filterPosts();
    $('[data-newsletter]')?.addEventListener('submit', e => { e.preventDefault(); toast('Newsletter preview — email delivery will be connected in a later phase.'); });
    $('[data-auth]')?.addEventListener('submit', e => { e.preventDefault(); toast('Authentication is a UI preview. No credentials have been sent or stored.'); });
    $('[data-share]')?.addEventListener('click', async () => { try { await navigator.clipboard.writeText(location.href); toast('Story link copied.'); } catch { toast('Copy the story URL from your browser’s address bar.'); } });
    $('[data-comment]')?.addEventListener('submit', e => { e.preventDefault(); const input = $('textarea', e.currentTarget); if (!input.value.trim()) return; const comment = document.createElement('div'); comment.className = 'comment'; const avatar = document.createElement('span'); avatar.className = 'avatar small green'; avatar.textContent = 'MR'; const body = document.createElement('div'); const name = document.createElement('strong'); name.textContent = 'You'; const time = document.createElement('small'); time.textContent = 'Just now · Preview comment'; const text = document.createElement('p'); text.textContent = input.value.trim(); body.append(name,time,text); comment.append(avatar,body); $('#comment-list').prepend(comment); input.value = ''; toast('Preview comment added. It will clear when you reload.'); });
    $('#mark-read')?.addEventListener('click', e => { $$('.unread').forEach(x => x.classList.remove('unread')); storage.set('notificationsRead', true); e.currentTarget.textContent = 'All caught up'; toast('Sample notifications marked as read.'); });
    if (storage.get('notificationsRead', false)) { $$('.notification-row').forEach(x => x.classList.remove('unread')); if ($('#mark-read')) $('#mark-read').textContent = 'All caught up'; }
    const prefForm = $('#preferences-form');
    if (prefForm) { for (const [name,value] of Object.entries(prefs)) { const field = prefForm.elements.namedItem(name); if (field) field.type === 'checkbox' ? field.checked = value : field.value = value; } prefForm.addEventListener('submit', e => { e.preventDefault(); for (const field of [...prefForm.elements].filter(x => x.name)) prefs[field.name] = field.type === 'checkbox' ? field.checked : field.value; storage.set('preferences', prefs); applyPrefs(); toast('Profile preview and reading preferences saved in this browser.'); }); }
    const editor = $('#story-editor');
    if (editor) {
        const draft = storage.get('draft', {}), fields = ['title','excerpt','content','category','tags'];
        fields.forEach(name => { if (draft[name]) editor.elements.namedItem(name).value = draft[name]; });
        function saveDraft(manual = false) { const value = Object.fromEntries(fields.map(name => [name, editor.elements.namedItem(name).value])); if (storage.set('draft', value)) { $('#draft-status').textContent = 'Draft saved in this browser.'; if (manual) toast('Draft saved. Come back whenever you’re ready.'); } }
        function updateCount() { const words = $('#story-content').value.trim().split(/\s+/).filter(Boolean).length; $('#word-count').textContent = `${words} words · ${Math.max(1, Math.ceil(words / 220))} min read`; }
        let draftTimer;
        editor.addEventListener('input', e => { updateCount(); if (e.target.type !== 'file') { $('#draft-status').textContent = 'Saving…'; clearTimeout(draftTimer); draftTimer = setTimeout(() => saveDraft(), 600); } });
        updateCount();
        $('#save-draft').addEventListener('click', () => saveDraft(true));
        window.addEventListener('pagehide', () => saveDraft());
        editor.addEventListener('submit', e => { e.preventDefault(); if (!$('#story-title').value.trim() || !$('#story-content').value.trim()) { toast('Add a title and your story before reviewing.'); return; } saveDraft(); $('#publish-dialog').showModal(); });
        $$('[data-close]').forEach(b => b.addEventListener('click', () => $('#publish-dialog').close()));
        $('#toggle-preview').addEventListener('click', e => { const content = $('#story-content'), preview = $('#story-preview'); const showing = preview.hidden; preview.hidden = !showing; content.hidden = showing; preview.innerHTML = markdown(content.value || 'Your story preview will appear here.'); e.currentTarget.textContent = showing ? 'Edit' : 'Preview'; });
        $$('[data-format]').forEach(b => b.addEventListener('click', () => { const field = $('#story-content'); if (field.hidden) $('#toggle-preview').click(); const {selectionStart:start,selectionEnd:end} = field; const text = field.value.slice(start,end) || 'your text'; const wrappers = {bold:['**','**'],italic:['*','*'],heading:['## ',''],code:['`','`']}; const [before,after] = wrappers[b.dataset.format]; field.setRangeText(before+text+after,start,end,'select'); field.focus(); field.dispatchEvent(new Event('input',{bubbles:true})); }));
        let coverUrl;
        $('#story-cover').addEventListener('change', e => { if (coverUrl) URL.revokeObjectURL(coverUrl); const file = e.target.files[0]; const img = $('#cover-preview'); if (!file) { img.hidden = true; return; } if (!file.type.startsWith('image/') || file.size > 8 * 1024 * 1024) { e.target.value = ''; img.hidden = true; toast('Choose an image smaller than 8 MB.'); return; } coverUrl = URL.createObjectURL(file); img.src = coverUrl; img.hidden = false; toast('Cover preview added. File uploads need the publishing backend.'); });
    }
    $$('[data-admin-tab]').forEach(b => b.addEventListener('click', () => { $$('[data-admin-panel]').forEach(p => p.hidden = p.dataset.adminPanel !== b.dataset.adminTab); $$('.admin-nav [data-admin-tab]').forEach(x => { x.classList.toggle('selected', x.dataset.adminTab === b.dataset.adminTab); x.setAttribute('aria-pressed', x.dataset.adminTab === b.dataset.adminTab); }); }));
    function filterAdmin() { let count = 0; $$('[data-review-row]').forEach(row => { row.hidden = !!$('#admin-status').value && $('[data-review-status]',row).textContent !== $('#admin-status').value; count += Number(!row.hidden); }); if ($('#admin-empty')) $('#admin-empty').hidden = count > 0; }
    $('#admin-status')?.addEventListener('change', filterAdmin);
    $$('[data-moderate]').forEach(b => b.addEventListener('click', () => { const status = $('[data-review-status]', b.closest('tr')); status.textContent = b.dataset.moderate; status.className = `status ${b.dataset.moderate === 'Published' ? 'published' : 'draft'}`; filterAdmin(); toast('Story status updated in this preview only.'); }));
    $$('[data-feature]').forEach(b => b.addEventListener('click', () => { const selected = b.getAttribute('aria-pressed') !== 'true'; b.setAttribute('aria-pressed',selected); b.textContent = selected ? 'Featured ✓' : 'Feature'; }));
    $$('[data-suspend]').forEach(b => b.addEventListener('click', () => { b.textContent = b.textContent === 'Suspend' ? 'Reactivate' : 'Suspend'; toast('Account status changed in this preview only.'); }));
    $$('[data-preview-change]').forEach(b => b.addEventListener('change', () => toast('Role changed in this preview only.')));
    $$('[data-resolve]').forEach(b => b.addEventListener('click', () => { b.disabled = true; b.textContent = 'Resolved ✓'; $('.tag',b.closest('.report-row')).textContent = 'Resolved'; toast('Sample report resolved.'); }));
    $('[data-taxonomy]')?.addEventListener('submit', e => { e.preventDefault(); const input = $('input',e.currentTarget); if (!input.value.trim()) return; const row = document.createElement('div'); row.className = 'taxonomy-row'; const title = document.createElement('strong'); title.textContent = input.value.trim(); const tag = document.createElement('span'); tag.className = 'tag'; tag.textContent = $('select',e.currentTarget).value; row.append(title,tag); $('#taxonomy-list').append(row); input.value = ''; toast('Added to this preview. Backend persistence is not connected.'); });
    const siteForm = $('[data-site-settings]');
    if (siteForm) { const saved = storage.get('siteSettings', {}); Object.entries(saved).forEach(([key,value]) => { const field = siteForm.elements.namedItem(key); if (field) field.type === 'checkbox' ? field.checked = value : field.value = value; }); siteForm.addEventListener('submit', e => { e.preventDefault(); const values = Object.fromEntries([...siteForm.elements].filter(x=>x.name).map(x=>[x.name,x.type==='checkbox'?x.checked:x.value])); storage.set('siteSettings',values); toast('Preview settings saved locally. Site behavior is unchanged.'); }); }
})();
