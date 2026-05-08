
    // Inject post IDs from PHP
    const ALL_POST_IDS = <?= json_encode($allPostIds ?? []) ?>;
    let aiCheckedCount = <?= (int)$totalAiChecked ?>;
    
    function incrementAiCheck() {
        aiCheckedCount++;
        const el = document.getElementById('statAiNum');
        if (el) el.innerText = aiCheckedCount;
    }

    // Count posts vs reels
    const postCards   = document.querySelectorAll('#view-posts .post-card');
    const storyCards  = document.querySelectorAll('#view-stories .reel-thumb-card');
    const reelCards   = document.querySelectorAll('#view-reels .reel-thumb-card');
    document.getElementById('count-posts').textContent = postCards.length;
    document.getElementById('count-stories').textContent = storyCards.length;
    document.getElementById('count-reels').textContent = reelCards.length;
    document.getElementById('bulkCount').textContent   = ALL_POST_IDS.length;

    // View switcher
    function switchView(view) {
        const postsEl   = document.getElementById('view-posts');
        const storiesEl = document.getElementById('view-stories');
        const reelsEl   = document.getElementById('view-reels');
        const boostsEl  = document.getElementById('view-boosts');
        
        document.getElementById('tab-posts').classList.toggle('active', view === 'posts');
        document.getElementById('tab-stories').classList.toggle('active', view === 'stories');
        document.getElementById('tab-reels').classList.toggle('active', view === 'reels');
        document.getElementById('tab-boosts').classList.toggle('active', view === 'boosts');

        postsEl.style.display = view === 'posts' ? 'flex' : 'none';
        
        if (view === 'stories') {
            storiesEl.classList.add('visible');
        } else {
            storiesEl.classList.remove('visible');
        }
        
        if (view === 'reels') {
            reelsEl.classList.add('visible');
        } else {
            reelsEl.classList.remove('visible');
        }
        
        if (view === 'boosts') {
            if (boostsEl) boostsEl.classList.add('visible');
        } else {
            if (boostsEl) boostsEl.classList.remove('visible');
        }
    }
    