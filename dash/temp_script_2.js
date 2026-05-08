
    function openComments(postId) {
        document.getElementById('commentsModal').style.display = 'flex';
        document.getElementById('commentsBody').innerHTML = '<div style="text-align:center; color:#6B7280; margin-top:40px;"><i class="fas fa-spinner fa-spin" style="font-size:24px;"></i><p style="margin-top:10px;">Loading comments...</p></div>';
        
        fetch('get_post_comments.php?PostID=' + postId)
            .then(res => res.text())
            .then(html => {
                document.getElementById('commentsBody').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('commentsBody').innerHTML = '<div style="color:red; text-align:center; margin-top:40px;">Failed to load comments.</div>';
            });
    }

    function closeComments() {
        document.getElementById('commentsModal').style.display = 'none';
        document.getElementById('commentsBody').innerHTML = ''; // clear memory
    }
    
    // Close modal when clicking outside
    document.getElementById('commentsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeComments();
        }
    });

    function acceptPost(postId) {
        Swal.fire({
            title: 'Accept Post?',
            text: "This post will go live to the main feed.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Yes, Accept it!',
            background: '#ffffff',
            customClass: {
                title: 'text-strong',
                popup: 'rounded-xl shadow-2xl border border-gray-100'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('ChangePostStatus.php?PostId=' + postId + '&PostStatus=ACTIVE')
                    .then(res => res.text())
                    .then(text => {
                        if(text.includes('Done') || text.includes(' Done ')) {
                            let badge = document.getElementById('post-status-badge-' + postId);
                            if (badge) {
                                badge.style.background = '#D1FAE5'; badge.style.color = '#065F46'; badge.style.borderColor = '#10B981';
                                badge.innerHTML = '✅ ACTIVE';
                            }
                            Swal.fire({
                                title: 'Accepted!',
                                text: 'The post is now active.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Error!', "Operation failed: " + text, 'error');
                        }
                    })
                    .catch(err => Swal.fire('Network Error', '', 'error'));
            }
        });
    }

    function rejectPost(postId) {
        Swal.fire({
            title: 'Reject & Delete?',
            text: "This will remove the post completely and hide it from the platform.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Yes, Delete it!',
            background: '#ffffff',
            customClass: {
                popup: 'rounded-xl shadow-2xl border border-red-50'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                let btn = document.querySelector('#post-card-' + postId + ' .post-stats:last-child');
                if (btn) {
                    btn.style.opacity = '0.5';
                    btn.style.pointerEvents = 'none';
                }
                
                fetch('ChangePostStatus.php?PostId=' + postId + '&PostStatus=REJECTED')
                    .then(res => res.text())
                    .then(text => {
                        if(text.includes('Done') || text.includes(' Done ')) {
                            let card = document.getElementById('post-card-' + postId);
                            card.style.transition = 'opacity 0.3s';
                            card.style.opacity = '0';
                            setTimeout(() => card.style.display = 'none', 300);
                            Swal.fire({
                                title: 'Deleted!',
                                text: 'The post has been removed.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Error!', "Operation failed: " + text, 'error');
                            if (btn) { btn.style.opacity = '1'; btn.style.pointerEvents = 'all'; }
                        }
                    })
                    .catch(err => {
                        Swal.fire('Network Error', '', 'error');
                        if (btn) { btn.style.opacity = '1'; btn.style.pointerEvents = 'all'; }
                    });
            }
        });
    }

    // ── AI MODERATION ────────────────────────────────────────────
    let currentModPostId = null;
    let currentModDecision = null;

    function runModeration(postId) {
        const btn = document.getElementById('ai-btn-' + postId);
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing...';
        currentModPostId = postId;

        const fd = new FormData();
        fd.append('PostId', postId);

        fetch('moderate_post.php', { method:'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                if (!data.success) {
                    btn.innerHTML = '<i class="fas fa-robot"></i> AI Check';
                    alert('AI Error: ' + (data.error || 'Unknown error'));
                    return;
                }
                
                incrementAiCheck();

                currentModDecision = data;

                // Swap button to badge
                const zone = document.getElementById('mod-zone-' + postId);
                const dec = data.decision.toLowerCase();
                const icons = { approved: '✅', rejected: '🚫', pending: '⚠️' };
                zone.innerHTML = `<span class="mod-badge ${dec}" onclick="showModResult('${postId}')">${icons[dec] || '🤖'} ${data.decision} (${data.confidence}%)</span>`;

                showModResult(postId);
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-robot"></i> AI Check';
                alert('Network error.');
            });
    }

    function showModResult(postId) {
        if (!currentModDecision || currentModDecision.postId != postId) return;
        const d = currentModDecision;
        const dec = d.decision.toLowerCase();

        // Decision row styling
        const row = document.getElementById('modDecisionRow');
        row.className = 'mod-decision-row ' + dec;
        document.getElementById('modIcon').textContent = dec === 'approved' ? '✅' : dec === 'rejected' ? '🚫' : '⚠️';
        document.getElementById('modDecisionLabel').textContent = d.decision;
        document.getElementById('modDecisionLabel').style.color = dec === 'approved' ? '#065F46' : dec === 'rejected' ? '#991B1B' : '#92400E';
        document.getElementById('modConfidence').textContent = 'Confidence: ' + d.confidence + '%';
        document.getElementById('modReason').textContent = d.reason || 'No reason provided.';

        // Category flags
        const cats = d.categories || {};
        const catLabels = { sexual:'Sexual', violence:'Violence', illegal:'Illegal', hate:'Hate Speech', political:'Political', scam:'Scam' };
        let catsHtml = '';
        for (const [k, v] of Object.entries(cats)) {
            catsHtml += `<span class="mod-cat-pill ${v ? 'flagged' : 'clean'}">${v ? '🔴' : '🟢'} ${catLabels[k] || k}</span>`;
        }
        document.getElementById('modCats').innerHTML = catsHtml;

        // Apply button label
        const applyBtn = document.getElementById('modApplyBtn');
        const dbStatus = dec === 'approved' ? 'ACTIVE' : dec === 'pending' ? 'PENDING' : 'REJECTED';
        applyBtn.innerHTML = `<i class="fas fa-check" style="margin-right:6px;"></i>Apply: Set to ${dbStatus}`;
        applyBtn.dataset.postId = postId;

        document.getElementById('modModal').style.display = 'flex';
    }

    function applyModDecision() {
        if (!currentModDecision) return;
        const postId = currentModDecision.postId;
        const btn = document.getElementById('modApplyBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';

        const fd = new FormData();
        fd.append('PostId', postId);
        fd.append('apply', '1');

        fetch('moderate_post.php', { method:'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                closeModModal();
                if (data.success) {
                    // If rejected, remove card
                    if (data.dbStatus === 'REJECTED') {
                        const card = document.getElementById('post-card-' + postId);
                        card.style.transition = 'opacity 0.3s';
                        card.style.opacity = '0';
                        setTimeout(() => card.style.display='none', 300);
                    }
                } else {
                    alert('Failed to apply: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Apply Decision to DB';
                alert('Network error applying decision.');
            });
    }

    function closeModModal() {
        document.getElementById('modModal').style.display = 'none';
    }
    document.getElementById('modModal').addEventListener('click', e => {
        if (e.target === document.getElementById('modModal')) closeModModal();
    });

    // ── BULK AI CHECK ──────────────────────────────────────────────────────────
    async function runBulkAI(isAuto = false) {
        const remaining = ALL_POST_IDS.filter(id => {
            // Only check those whose mod-zone still has the original button (not yet AI-checked)
            const zone = document.getElementById('mod-zone-' + id);
            return zone && zone.querySelector('.ai-mod-btn');
        });

        if (remaining.length === 0) {
            if (!isAuto) alert('All posts in this feed have already been AI checked!');
            return;
        }

        if (!isAuto) {
            if (!confirm(`Run AI moderation on ${remaining.length} unchecked post(s)? This may take a few moments.`)) return;
        }

        const btn = document.getElementById('bulkAiBtn');
        btn.disabled = true;
        btn.querySelector('span:first-of-type').textContent = 'Checking...';

        const progress = document.getElementById('bulkProgress');
        const bar      = document.getElementById('bulkProgressBar');
        progress.style.display = 'block';

        let done = 0;
        for (const postId of remaining) {
            const zone = document.getElementById('mod-zone-' + postId);
            if (!zone || !zone.querySelector('.ai-mod-btn')) {
                done++; continue; // skip already checked
            }

            // Mark as scanning
            const aiBtn = document.getElementById('ai-btn-' + postId);
            if (aiBtn) {
                aiBtn.disabled = true;
                aiBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning...';
            }

            try {
                const fd = new FormData();
                fd.append('PostId', postId);
                fd.append('apply', '1'); // Auto-apply decision to DB

                const res  = await fetch('moderate_post.php', { method:'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    aiCheckedCount++;
                    document.getElementById('statAiNum').textContent = aiCheckedCount;

                    const dec   = data.decision.toLowerCase();
                    const icons = { approved:'✅', rejected:'🚫', pending:'⚠️' };
                    zone.innerHTML = `<span class="mod-badge ${dec}" onclick="currentModDecision=${JSON.stringify(data).replace(/'/g,"&apos;")};showModResult(${postId})">${icons[dec]||'🤖'} ${data.decision} (${data.confidence}%)</span>`;

                    // Auto-hide rejected cards
                    if (data.dbStatus === 'REJECTED') {
                        const card = document.getElementById('post-card-' + postId);
                        if (card) {
                            card.style.transition = 'opacity 0.4s';
                            card.style.opacity = '0';
                            setTimeout(() => card.style.display = 'none', 400);
                        }
                    }
                } else {
                    if (aiBtn) { aiBtn.disabled = false; aiBtn.innerHTML = '<i class="fas fa-robot"></i> AI Check'; }
                }
            } catch(e) {
                if (aiBtn) { aiBtn.disabled = false; aiBtn.innerHTML = '<i class="fas fa-robot"></i> AI Check'; }
            }

            done++;
            const pct = Math.round((done / remaining.length) * 100);
            bar.style.width = pct + '%';
            document.getElementById('bulkCount').textContent = (remaining.length - done) + ' left';
        }

        // Done
        btn.disabled = false;
        btn.querySelector('span:first-of-type').textContent = 'AI Check Unchecked Posts';
        document.getElementById('bulkCount').textContent = '✓ Done';
        setTimeout(() => {
            progress.style.display = 'none';
            bar.style.width = '0%';
            document.getElementById('bulkCount').textContent = '0';
        }, 3000);
    }
    