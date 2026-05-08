
    let _reelObserver = null;

    function openReelPlayer(startId) {
        if (typeof REELS_DATA === 'undefined' || !REELS_DATA.length) return;
        const modal = document.getElementById('reelPlayerModal');
        const wrap  = document.getElementById('reelPlayerWrap');
        const closeBtn = document.getElementById('reelCloseBtn');

        // Build all reel items
        wrap.innerHTML = '';
        REELS_DATA.forEach((r, idx) => {
            const item = document.createElement('div');
            item.className = 'reel-player-item';
            item.id = 'rpi-' + r.id;
            item.innerHTML = `
                <video id="rpv-${r.id}" src="${r.src}" ${r.thumb ? 'poster="'+r.thumb+'"' : ''}
                    loop playsinline preload="metadata" muted
                    style="width:100%;height:100%;object-fit:contain;background:#000;cursor:pointer;" onclick="toggleRPV('${r.id}')"></video>
                
                <!-- Floating Right Controls -->
                <div style="position:absolute; right:15px; bottom:50%; transform:translateY(50%); display:flex; flex-direction:column; gap:25px; z-index:10;">
                    
                    <!-- Sound Toggle -->
                    <button style="background:transparent;border:none;color:#fff;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;" 
                            onclick="event.stopPropagation(); toggleMute('${r.id}')" id="mute-btn-${r.id}">
                        <div style="width:50px;height:50px;border-radius:50%;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.2);transition:0.2s;">
                             <i class="fas fa-volume-mute" style="font-size:20px;" id="mute-icon-${r.id}"></i>
                        </div>
                        <span style="font-size:12px;font-weight:600;text-shadow:0 1px 3px rgba(0,0,0,0.8);" id="mute-text-${r.id}">Sound Off</span>
                    </button>
                    
                    <!-- Status Public/Reject Toggle -->
                    ${r.postId ? `
                    <button style="background:transparent;border:none;color:#fff;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;" 
                            onclick="event.stopPropagation(); toggleReelStatus('${r.postId}')" id="status-btn-${r.postId}">
                        <div style="width:50px;height:50px;border-radius:50%;background:rgba(16,185,129,0.25);display:flex;align-items:center;justify-content:center;backdrop-filter:blur(10px);border:1px solid rgba(16,185,129,0.5);transition:0.2s;" id="status-circle-${r.postId}">
                             <i class="fas fa-check" style="font-size:20px;color:#34D399;" id="status-icon-${r.postId}"></i>
                        </div>
                        <span style="font-size:12px;font-weight:600;text-shadow:0 1px 3px rgba(0,0,0,0.8);color:#34D399;" id="status-text-${r.postId}">Public</span>
                    </button>
                    ` : ''}
                    
                    <!-- AI Check Button / Badge -->
                    ${r.postId && !r.aiChecked ? `
                    <button style="background:transparent;border:none;color:#fff;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;" 
                            onclick="event.stopPropagation(); checkReelAI('${r.postId}', '${r.type}')" id="ai-reel-btn-${r.postId}">
                        <div style="width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg, rgba(99,102,241,0.5), rgba(139,92,246,0.5));display:flex;align-items:center;justify-content:center;backdrop-filter:blur(10px);border:1px solid rgba(139,92,246,0.5);transition:0.2s;">
                             <i class="fas fa-robot" style="font-size:20px;color:#fff;"></i>
                        </div>
                        <span style="font-size:12px;font-weight:600;text-shadow:0 1px 3px rgba(0,0,0,0.8);color:#fff;">AI Check</span>
                    </button>
                    ` : r.postId && r.aiChecked ? `
                    <div style="background:transparent;color:#fff;display:flex;flex-direction:column;align-items:center;gap:6px;">
                        <div style="width:50px;height:50px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.3);">
                             <i class="fas fa-check-double" style="font-size:20px;color:#fff;"></i>
                        </div>
                        <span style="font-size:12px;font-weight:600;text-shadow:0 1px 3px rgba(0,0,0,0.8);color:#fff;">Checked</span>
                    </div>
                    ` : ''}
                </div>`;
            wrap.appendChild(item);
        });

        modal.classList.add('open');
        closeBtn.classList.add('open');
        document.body.style.overflow = 'hidden'; // prevent page scroll

        // Scroll to start item
        setTimeout(() => {
            const target = document.getElementById('rpi-' + startId);
            if (target) target.scrollIntoView({ behavior: 'instant', block: 'start' });
            setupReelObserver();
        }, 50);
    }

    function setupReelObserver() {
        if (_reelObserver) _reelObserver.disconnect();
        _reelObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const vid = entry.target.querySelector('video');
                if (!vid) return;
                if (entry.isIntersecting && entry.intersectionRatio > 0.7) {
                    vid.play().catch(() => {});
                } else {
                    vid.pause();
                }
            });
        }, { root: document.getElementById('reelPlayerModal'), threshold: 0.7 });

        document.querySelectorAll('.reel-player-item').forEach(item => {
            _reelObserver.observe(item);
        });
    }

    function toggleRPV(id) {
        const v = document.getElementById('rpv-' + id);
        if (!v) return;
        if (v.paused) v.play(); else v.pause();
    }

    function toggleMute(id) {
        const v = document.getElementById('rpv-' + id);
        const icon = document.getElementById('mute-icon-' + id);
        const text = document.getElementById('mute-text-' + id);
        if (!v) return;
        
        v.muted = !v.muted;
        if (v.muted) {
            icon.className = 'fas fa-volume-mute';
            text.innerText = 'Sound Off';
        } else {
            icon.className = 'fas fa-volume-up';
            text.innerText = 'Sound On';
        }
    }

    function toggleReelStatus(postId) {
        const circle = document.getElementById('status-circle-' + postId);
        const icon = document.getElementById('status-icon-' + postId);
        const text = document.getElementById('status-text-' + postId);
        const isPublic = (text.innerText === 'Public');

        // Fire status update to backend without blocking UI
        fetch('update_post_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'PostId=' + postId + '&Status=' + (isPublic ? 'REJECTED' : 'ACTIVE')
        }).catch(err => console.error(err));
        
        if (isPublic) {
            text.innerText = 'Rejected';
            text.style.color = '#EF4444';
            icon.className = 'fas fa-ban';
            icon.style.color = '#EF4444';
            circle.style.background = 'rgba(239,68,68,0.25)';
            circle.style.borderColor = 'rgba(239,68,68,0.5)';
        } else {
            text.innerText = 'Public';
            text.style.color = '#34D399';
            icon.className = 'fas fa-check';
            icon.style.color = '#34D399';
            circle.style.background = 'rgba(16,185,129,0.25)';
            circle.style.borderColor = 'rgba(16,185,129,0.5)';
        }
    }

    function checkReelAI(postId, type) {
        const btn = document.getElementById('ai-reel-btn-' + postId);
        if (!btn) return;
        
        // Use existing DOM element logic inside showModResult
        currentModDecision = null;
        
        // Show scanning state in modal
        btn.querySelector('i').className = 'fas fa-spinner fa-spin';
        btn.querySelector('span').innerText = 'Scanning...';

        fetch('moderate_post.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'PostId=' + postId + '&Type=' + type
        })
        .then(r => r.json())
        .then(data => {
            btn.querySelector('i').className = 'fas fa-robot';
            btn.querySelector('span').innerText = 'AI Check';

            if (!data.success) {
                alert('AI Error: ' + (data.error || 'Unknown error'));
                return;
            }
            
            incrementAiCheck();

            // Sync visual status automatically based on AI decision without requiring click flow
            if (data.decision !== 'PENDING') {
                const isCurrentlyPublic = document.getElementById('status-text-' + postId).innerText === 'Public';
                const aiWantsPublic = data.decision === 'APPROVED';
                
                if (isCurrentlyPublic !== aiWantsPublic) {
                     toggleReelStatus(postId); // flip it to match
                }
            }

            // Still show the full AI result popup over the video
            currentModDecision = data;
            showModResult(postId);
        })
        .catch(err => {
            btn.querySelector('i').className = 'fas fa-robot';
            btn.querySelector('span').innerText = 'AI Check';
            alert('Network error.');
        });
    }

    // ── Dedicated Handlers for the New Image Stories Grid ──
    function acceptStory(postId, shopId) {
        Swal.fire({
            title: 'Accept Story?',
            text: "This story will be active and visible.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Yes, Accept it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('ChangeStoryStatus.php?PostId=' + postId + '&StoryStatus=ACTIVE&ShopID=' + shopId)
                    .then(res => res.text())
                    .then(text => {
                        if(text.includes('Done') || text.includes(' Done ')) {
                            document.getElementById('story-card-' + postId).style.opacity = '1';
                            document.getElementById('accept-story-btn-' + postId).style.opacity = '0.4';
                            document.getElementById('accept-story-btn-' + postId).style.pointerEvents = 'none';
                            document.getElementById('reject-story-btn-' + postId).style.opacity = '1';
                            document.getElementById('reject-story-btn-' + postId).style.pointerEvents = 'auto';
                            let statusBadge = document.querySelector('#story-card-' + postId + ' .reel-status');
                            if(statusBadge) { statusBadge.innerHTML = 'ACTIVE'; statusBadge.style.background = '#D1FAE5'; statusBadge.style.color = '#065F46'; }
                            
                            Swal.fire({ title: 'Accepted!', icon: 'success', timer: 1500, showConfirmButton: false });
                        } else {
                            Swal.fire('Error!', "Operation failed: " + text, 'error');
                        }
                    })
                    .catch(err => Swal.fire('Network Error', '', 'error'));
            }
        });
    }

    function rejectStory(postId, shopId) {
        Swal.fire({
            title: 'Reject & Hide?',
            text: "This story will be hidden from the platform.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Yes, Reject it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('reject-story-btn-' + postId).style.opacity = '0.4';
                document.getElementById('reject-story-btn-' + postId).style.pointerEvents = 'none';
                
                fetch('ChangeStoryStatus.php?PostId=' + postId + '&StoryStatus=REJECTED&ShopID=' + shopId)
                    .then(res => res.text())
                    .then(text => {
                        if(text.includes('Done') || text.includes(' Done ')) {
                            document.getElementById('story-card-' + postId).style.opacity = '0.6';
                            document.getElementById('accept-story-btn-' + postId).style.opacity = '1';
                            document.getElementById('accept-story-btn-' + postId).style.pointerEvents = 'auto';
                            let statusBadge = document.querySelector('#story-card-' + postId + ' .reel-status');
                            if(statusBadge) { statusBadge.innerHTML = 'REJECTED'; statusBadge.style.background = '#FEE2E2'; statusBadge.style.color = '#991B1B'; }

                            // Make the visual transition like Posts
                            const card = document.getElementById('story-card-' + postId);
                            card.style.transition = 'opacity 0.4s, transform 0.4s';
                            card.style.transform = 'scale(0.95)';
                            setTimeout(() => card.style.display = 'none', 400);

                            Swal.fire({ title: 'Rejected!', icon: 'success', timer: 1500, showConfirmButton: false });
                        } else {
                            Swal.fire('Error!', "Operation failed: " + text, 'error');
                            document.getElementById('reject-story-btn-' + postId).style.opacity = '1';
                            document.getElementById('reject-story-btn-' + postId).style.pointerEvents = 'auto';
                        }
                    })
                    .catch(err => Swal.fire('Network Error', '', 'error'));
            }
        });
    }

    function checkStoryAI(postId, shopId) {
        const btn = document.getElementById('ai-story-btn-' + postId);
        if (!btn) return;
        
        currentModDecision = null;
        btn.querySelector('i').className = 'fas fa-spinner fa-spin';

        fetch('moderate_post.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'PostId=' + postId + '&Type=story'
        })
        .then(r => r.json())
        .then(data => {
            btn.querySelector('i').className = 'fas fa-robot';

            if (!data.success) {
                alert('AI Error: ' + (data.error || 'Unknown error'));
                return;
            }
            incrementAiCheck();

            // Transform button to badge dynamically
            const parent = btn.parentElement;
            const newBadge = document.createElement('div');
            newBadge.style = "background:rgba(16,185,129,0.95); color:#fff; border-radius:50%; width:32px; height:32px; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(0,0,0,0.3);";
            newBadge.title = "AI Checked";
            newBadge.innerHTML = '<i class="fas fa-check-double" style="font-size:12px;"></i>';
            parent.insertBefore(newBadge, btn);
            parent.removeChild(btn);

            if (data.decision === 'REJECTED') {
                // Silently push the rejection to the database without a visual SweetAlert
                fetch('ChangeStoryStatus.php?PostId=' + postId + '&StoryStatus=REJECTED&ShopID=' + shopId).then(() => {
                    const card = document.getElementById('story-card-' + postId);
                    if(card) {
                        card.style.transition = 'opacity 0.4s, transform 0.4s';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => card.style.display = 'none', 400);
                    }
                });
            }

            currentModDecision = data;
            showModResult(postId);
        })
        .catch(err => {
            btn.querySelector('i').className = 'fas fa-robot';
            alert('Network error.');
        });
    }

    function toggleBoostStatus(boostsByShopId) {
        if(!confirm("هل أنت متأكد من تغيير حالة هذا الإعلان الممول؟")) return;
        
        const btn = document.getElementById('status-boost-btn-' + boostsByShopId);
        const icon = document.getElementById('status-boost-icon-' + boostsByShopId);
        if (!btn) return;
        
        const isCurrentlyRejected = btn.getAttribute('data-status') === 'REJECTED';
        const newStatus = isCurrentlyRejected ? 'Active' : 'Rejected'; // Assuming 'Active' and 'Rejected' based on PHP script

        // Note: ChangeBoostStatues.php performs a redirect (window.location.href). 
        // Calling it via fetch will likely follow the redirect or return HTML.
        // We'll optimistically update the UI, but it's important to note the backend might redirect.
        fetch('ChangeBoostStatues.php?BoostsByShopID=' + boostsByShopId + '&BoostStatus=' + newStatus)
            .then(res => res.text())
            .then(text => {
                // ChangeBoostStatues.php injects scripts for redirection on success/failure,
                // so we consider any response back a successful trigger for our optimistic UI update here.
                btn.setAttribute('data-status', newStatus === 'Active' ? 'ACTIVE' : 'REJECTED');
                if (newStatus === 'Rejected') {
                    icon.className = 'fas fa-undo';
                    document.getElementById('boost-card-' + boostsByShopId).style.opacity = '0.6';
                    const card = document.getElementById('boost-card-' + boostsByShopId);
                    const badge = card.querySelector('.reel-status-badge');
                    if (badge) {
                        badge.innerHTML = '<i class="fas fa-ban"></i> REJECTED';
                        badge.style.background = '#FEE2E2';
                        badge.style.color = '#991B1B';
                    }
                } else {
                    icon.className = 'fas fa-ban';
                    document.getElementById('boost-card-' + boostsByShopId).style.opacity = '1';
                    const card = document.getElementById('boost-card-' + boostsByShopId);
                    const badge = card.querySelector('.reel-status-badge');
                    if (badge) {
                        badge.innerHTML = '<i class="fas fa-check"></i> ACTIVE';
                        badge.style.background = '#D1FAE5';
                        badge.style.color = '#065F46';
                    }
                }
            })
            .catch(err => {
                console.error(err);
                alert("حدث خطأ في الشبكة");
            });
    }

    function closeReelPlayer() {
        const modal = document.getElementById('reelPlayerModal');
        const wrap  = document.getElementById('reelPlayerWrap');
        const closeBtn = document.getElementById('reelCloseBtn');
        // Pause all
        wrap.querySelectorAll('video').forEach(v => { v.pause(); v.src = ''; });
        if (_reelObserver) { _reelObserver.disconnect(); _reelObserver = null; }
        wrap.innerHTML = '';
        modal.classList.remove('open');
        closeBtn.classList.remove('open');
        document.body.style.overflow = '';
    }

    // Close on Escape key
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeReelPlayer(); });

    function escHtml(str) {
        if (!str) return '';
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    
    // Automatically trigger Bulk AI explicitly on unchecked posts moments after UI loads
    setTimeout(() => {
        runBulkAI(true);
    }, 2500);

    // ── SMART BACKGROUND AI WORKER HEARTBEAT ──────────────────────────────
    // As long as the administrator keeps this dashboard open, the browser acts as the background AI worker!
    // It silently polls the database every 5 seconds for any new mobile uploads and forces the AI check.
    setInterval(() => {
        fetch('tick_ai_worker.php')
          .then(res => res.json())
          .then(data => {
              if (data.success && data.checkedCount > 0) {
                  // A new post was automatically caught and checked by the background AI!
                  // We silently increment the UI stats so the admin sees the AI is working!
                  for (let i = 0; i < data.checkedCount; i++) incrementAiCheck();
              }
          })
          .catch(e => { /* Silently ignore network stutters */ });
    }, 5000);
    