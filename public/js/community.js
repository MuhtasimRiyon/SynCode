(() => {
    'use strict';
    const $ = (s, root = document) => root.querySelector(s);
    const $$ = (s, root = document) => [...root.querySelectorAll(s)];
    const state = window.communityState || {base: '', postIds: {}, bookmarks: [], likes: [], following: [], preferences: {}};
    const authenticated = document.body.dataset.authenticated === 'true';
    let bookmarks = state.bookmarks, likes = state.likes, following = state.following;
    let toastTimer;
    function toast(message) { const el = $('.toast'); if (!el) return; el.textContent = message; el.hidden = false; clearTimeout(toastTimer); toastTimer = setTimeout(() => el.hidden = true, 4500); }
    function requireLogin() { if (authenticated) return true; window.location.assign(document.body.dataset.loginUrl); return false; }
    async function request(path) {
        const response = await fetch(state.base + path, {method:'POST', credentials:'same-origin', headers: {'Accept':'application/json', 'X-CSRF-TOKEN':$('meta[name="csrf-token"]').content}});
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'Unable to save. Please reload and try again.');
        return data;
    }
    $$('[data-demo-login]').forEach(button => button.addEventListener('click', event => {
        const form = $('#login-form'); if (!form) return; event.preventDefault();
        form.elements.namedItem('email').value = button.dataset.email;
        form.elements.namedItem('password').value = button.dataset.password;
        $$('[data-demo-login]').forEach(option => option.setAttribute('aria-pressed', option === button));
        $('[data-demo-status]').textContent = `${button.textContent.trim()} credentials ready. Click Sign in.`;
        $('button[type="submit"]', form).focus();
    }));
    document.body.classList.toggle('comfortable', !!state.preferences.comfortable);
    document.body.classList.toggle('reduce-motion', !!state.preferences.reduceMotion);
    $('[data-menu]')?.addEventListener('click', e => { const open = $('#navigation').classList.toggle('open'); e.currentTarget.setAttribute('aria-expanded', open); });
    document.addEventListener('keydown', e => {
        if (e.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) { const input = $('.global-search input'); if (input && input.getClientRects().length) { e.preventDefault(); input.focus(); } }
        if (e.key === 'Escape') { $('#navigation')?.classList.remove('open'); $('[data-menu]')?.setAttribute('aria-expanded', 'false'); }
    });
    function syncButtons() {
        $$('[data-bookmark]').forEach(b => b.setAttribute('aria-pressed', bookmarks.includes(b.dataset.bookmark)));
        $$('[data-like]').forEach(b => b.setAttribute('aria-pressed', likes.includes(b.dataset.like)));
        $$('[data-follow]').forEach(b => { const active = following.includes(b.dataset.follow); b.setAttribute('aria-pressed', active); b.textContent = b.classList.contains('follow-button') ? (active ? '✓' : '+') : (active ? 'Following ✓' : 'Follow'); });
    }
    const setActive = (list, key, active) => active ? [...new Set([...list, key])] : list.filter(x => x !== key);
    for (const kind of ['like','bookmark']) $$(`[data-${kind}]`).forEach(button => button.addEventListener('click', async () => {
        if (!requireLogin()) return;
        const slug = button.dataset[kind]; button.disabled = true;
        try {
            const data = await request(`/posts/${state.postIds[slug]}/reactions/${kind}`);
            if (kind === 'like') {
                likes = setActive(likes, slug, data.active);
                $$('[data-like]').filter(b => b.dataset.like === slug).forEach(b => { const count = $('span',b); if (count) count.textContent = data.count; });
                $$('[data-post]').filter(c=>c.dataset.slug===slug).forEach(c=>c.dataset.likes=data.count);
            } else { bookmarks = setActive(bookmarks, slug, data.active); toast(data.active ? 'Story saved to your reading corner.' : 'Bookmark removed.'); }
            syncButtons(); filterPosts();
        } catch (error) { toast(error.message); } finally { button.disabled = false; }
    }));
    $$('[data-follow]').forEach(button => button.addEventListener('click', async () => {
        if (!requireLogin()) return; button.disabled = true;
        try { const data = await request(`/authors/${button.dataset.follow}/follow`); following = setActive(following,button.dataset.follow,data.active); syncButtons(); filterPosts(); }
        catch (error) { toast(error.message); } finally { button.disabled = false; }
    }));
    syncButtons();
    const serverFeed = document.body.dataset.page === 'feed';
    function navigateFeed(key, value) { const url = new URL(location.href); value ? url.searchParams.set(key,value) : url.searchParams.delete(key); url.searchParams.delete('page'); window.location.assign(url); }
    let currentTopic = new URLSearchParams(location.search).get('topic') || '', currentTab = new URLSearchParams(location.search).get('tab') || 'all';
    let search = new URLSearchParams(location.search).get('q') || '';
    const cards = $$('[data-post]'); cards.forEach((card,i)=>card.dataset.originalOrder=i);
    function filterPosts() {
        if (serverFeed) return;
        let visible = 0;
        cards.forEach(card => { const show = (document.body.dataset.page !== 'bookmarks' || bookmarks.includes(card.dataset.slug)) && (!currentTopic || card.dataset.category === currentTopic) && (!search || card.textContent.toLowerCase().includes(search.toLowerCase())) && (currentTab !== 'following' || following.includes(card.dataset.author)); card.hidden=!show; visible+=Number(show); });
        if ($('[data-empty]')) $('[data-empty]').hidden = visible > 0;
        if ($('.feed-end')) $('.feed-end').hidden = visible === 0;
    }
    $$('[data-topic]').forEach(button => { button.classList.toggle('selected',button.dataset.topic===currentTopic); button.setAttribute('aria-pressed',button.dataset.topic===currentTopic); button.addEventListener('click',()=>{ if(serverFeed){navigateFeed('topic',button.dataset.topic);return;} currentTopic=button.dataset.topic; $$('[data-topic]').forEach(b=>{b.classList.toggle('selected',b===button);b.setAttribute('aria-pressed',b===button);});filterPosts(); }); });
    $$('[data-tab]').forEach(button=>button.addEventListener('click',()=>{ if (button.dataset.tab==='following'&&!requireLogin()) return; if(serverFeed){navigateFeed('tab',button.dataset.tab);return;} currentTab=button.dataset.tab; $$('[data-tab]').forEach(b=>{b.classList.toggle('selected',b===button);b.setAttribute('aria-pressed',b===button);}); const parent=$('#post-list'); if(parent) [...cards].sort((a,b)=>currentTab==='trending'?Number(b.dataset.likes)-Number(a.dataset.likes):Number(a.dataset.originalOrder)-Number(b.dataset.originalOrder)).forEach(card=>parent.appendChild(card));filterPosts(); }));
    $$('[data-tab]').forEach(button=>{button.classList.toggle('selected',button.dataset.tab===currentTab);button.setAttribute('aria-pressed',button.dataset.tab===currentTab);});
    $$('[data-story-status]').forEach(button=>button.addEventListener('click',()=>{ $$('[data-story-status]').forEach(b=>{b.classList.toggle('selected',b===button);b.setAttribute('aria-pressed',b===button);}); $$('[data-my-story]').forEach(row=>row.hidden=!!button.dataset.storyStatus&&row.dataset.myStory!==button.dataset.storyStatus); }));
    filterPosts();
    $('[data-share]')?.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(location.href);toast('Story link copied.');}catch{toast('Copy the story URL from your browser’s address bar.');}});
    $$('[data-confirm]').forEach(form=>form.addEventListener('submit',e=>{if(!window.confirm(form.dataset.confirm))e.preventDefault();}));
    const editor=$('#story-editor');
    if(editor) {
        const content=$('#story-content');
        function updateCount(){const words=content.value.trim().split(/\s+/).filter(Boolean).length;$('#word-count').textContent=`${words} words · ${Math.max(1,Math.ceil(words/220))} min read`;}
        content.addEventListener('input',updateCount);updateCount();
        function markdown(value) { return value.split('\n').map(line=>{let text=line.replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])).replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>').replace(/\*(.+?)\*/g,'<em>$1</em>').replace(/`(.+?)`/g,'<code>$1</code>');return text.startsWith('## ')?`<h2>${text.slice(3)}</h2>`:text.startsWith('# ')?`<h2>${text.slice(2)}</h2>`:`<p>${text||'<br>'}</p>`;}).join(''); }
        $('#toggle-preview').addEventListener('click',e=>{const preview=$('#story-preview');const showing=preview.hidden;preview.hidden=!showing;content.hidden=showing;preview.innerHTML=markdown(content.value);e.currentTarget.textContent=showing?'Edit':'Preview';});
        $$('[data-format]').forEach(button=>button.addEventListener('click',()=>{if(content.hidden)$('#toggle-preview').click();const {selectionStart:start,selectionEnd:end}=content;const wrappers={bold:['**','**'],italic:['*','*'],heading:['## ',''],code:['`','`']};const [before,after]=wrappers[button.dataset.format];content.setRangeText(before+(content.value.slice(start,end)||'your text')+after,start,end,'select');content.focus();updateCount();}));
        editor.addEventListener('submit', e => { if(e.submitter?.value==='schedule'&&!editor.elements.namedItem('published_at').value){e.preventDefault();toast('Choose a future publication date and time.');} });
        let coverUrl;
        $('#story-cover').addEventListener('change',e=>{if(coverUrl)URL.revokeObjectURL(coverUrl);const file=e.target.files[0],img=$('#cover-preview');if(!file){img.hidden=true;return;}if(!['image/jpeg','image/png','image/webp','image/gif'].includes(file.type)||file.size>8*1024*1024){e.target.value='';img.hidden=true;toast('Choose a JPG, PNG, WebP or GIF smaller than 8 MB.');return;}coverUrl=URL.createObjectURL(file);img.src=coverUrl;img.hidden=false;});
    }
    $$('[data-admin-tab]').forEach(button=>button.addEventListener('click',()=>{const key=button.dataset.adminTab;$$('[data-admin-panel]').forEach(panel=>panel.hidden=panel.dataset.adminPanel!==key);$$('.admin-nav [data-admin-tab]').forEach(b=>{b.classList.toggle('selected',b.dataset.adminTab===key);b.setAttribute('aria-pressed',b.dataset.adminTab===key);});const url=new URL(location.href);url.searchParams.set('tab',key);history.replaceState(null,'',url);}));
    const tab=new URLSearchParams(location.search).get('tab');if(tab)$$('.admin-nav [data-admin-tab]').find(b=>b.dataset.adminTab===tab)?.click();
    $('#admin-status')?.addEventListener('change',e=>{let count=0;$$('[data-review-row]').forEach(row=>{row.hidden=!!e.target.value&&$('[data-review-status]',row).textContent!==e.target.value;count+=Number(!row.hidden);});$('#admin-empty').hidden=count>0;});
})();
