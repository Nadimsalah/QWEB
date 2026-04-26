    <!-- COMMENTS MODAL -->
    <div id="comments-modal-overlay"
        style="position: fixed; inset: 0; z-index: 10000; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); display: none; align-items: flex-end; justify-content: center; opacity: 0; transition: opacity 0.3s ease;">
        <div id="comments-modal"
            style="width: 100%; max-width: 600px; height: 75vh; background: #111; border-top-left-radius: 24px; border-top-right-radius: 24px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; flex-direction: column; transform: translateY(100%); transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1); box-shadow: 0 -10px 40px rgba(0,0,0,0.5);">

            <div style="width: 100%; display: flex; justify-content: center; padding: 12px 0; cursor: pointer;"
                onclick="closeCommentModal()">
                <div style="width: 40px; height: 5px; background: rgba(255,255,255,0.2); border-radius: 10px;"></div>
            </div>

            <h3
                style="text-align: center; font-size: 16px; font-weight: 600; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff;">
                Comments</h3>

            <div id="comments-feed" style="flex: 1; overflow-y: auto; padding: 20px;">
                <!-- Demo Comment -->
                <div style="display: flex; gap: 12px; margin-bottom: 24px;">
                    <img src="https://ui-avatars.com/api/?name=User&background=random"
                        style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: baseline; gap: 8px;">
                            <span style="font-weight: 600; font-size: 14px; color: #fff;">Sarah M.</span>
                            <span style="font-size: 12px; color: rgba(255,255,255,0.5);">2h</span>
                        </div>
                        <p style="font-size: 14px; color: rgba(255,255,255,0.8); margin-top: 4px; line-height: 1.4;">
                            This looks amazing! Can't wait to try it out.</p>
                        <div style="display: flex; gap: 16px; margin-top: 8px;">
                            <span
                                style="font-size: 12px; color: rgba(255,255,255,0.5); font-weight: 500; cursor: pointer; transition: color 0.2s;"
                                onmouseover="this.style.color='#fff'"
                                onmouseout="this.style.color='rgba(255,255,255,0.5)'">Reply</span>
                            <span
                                style="font-size: 12px; color: rgba(255,255,255,0.5); font-weight: 500; cursor: pointer;"><i
                                    class="fa-regular fa-heart"></i> 12</span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                style="padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.05); background: #0a0a0a; border-bottom-left-radius: 24px; border-bottom-right-radius: 24px; padding-bottom: 32px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <img src="https://ui-avatars.com/api/?name=Me&background=2cb5e8&color=fff"
                        style="width: 36px; height: 36px; border-radius: 50%;">
                    <div style="flex: 1; position: relative;">
                        <input type="text" id="comment-input" placeholder="Add a comment..."
                            style="width: 100%; height: 44px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 22px; padding: 0 44px 0 16px; color: #fff; font-size: 14px; font-family: Inter, sans-serif; outline: none; transition: border-color 0.2s;"
                            onfocus="this.style.borderColor='rgba(255,255,255,0.3)'"
                            onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                        <button onclick="sendComment()"
                            style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 28px; height: 28px; border-radius: 50%; background: #fff; color: #000; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s;"
                            onmouseover="this.style.transform='translateY(-50%) scale(1.1)'"
                            onmouseout="this.style.transform='translateY(-50%) scale(1)'">
                            <i class="fa-solid fa-arrow-up" style="font-size: 12px;"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <script>
        var commentModalOverlay = document.getElementById('comments-modal-overlay');
        var commentModal = document.getElementById('comments-modal');
        var commentInput = document.getElementById('comment-input');
        var commentsFeed = document.getElementById('comments-feed');

        var currentPostId = null;
        var currentShopName = null;

        function stringToColor(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                hash = str.charCodeAt(i) + ((hash << 5) - hash);
            }
            let c = (hash & 0x00FFFFFF).toString(16).toUpperCase();
            return "00000".substring(0, 6 - c.length) + c;
        }

        function timeAgo(dateString) {
            if (!dateString) return '1m';
            var d = new Date(dateString.replace(' ', 'T'));
            var now = new Date();
            var diff = Math.floor((now - d) / 1000);
            if (diff < 60) return diff + 's';
            if (diff < 3600) return Math.floor(diff / 60) + 'm';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h';
            return Math.floor(diff / 86400) + 'd';
        }

        function handleLike(btn) {
            const uid = (document.cookie.match('(^|;) ?qoon_user_id=([^;]*)(;|$)')||[])[2];
            const isLoggedIn = (uid && uid !== '0' && uid !== '');
            if (!isLoggedIn) {
                if (typeof openSignup === 'function') {
                    openSignup();
                } else {
                    window.location.href = 'index.php?auth_required=1';
                }
                return;
            }
            // Visual toggle for authenticated users (API call can be added here)
            const icon = btn.querySelector('i');
            if (icon.classList.contains('fa-regular')) {
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
                icon.style.color = '#ff3b30';
            } else {
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
                icon.style.color = '';
            }
        }

        function openCommentModal(postId, shopName) {
            const uid = (document.cookie.match('(^|;) ?qoon_user_id=([^;]*)(;|$)')||[])[2];
            const isLoggedIn = (uid && uid !== '0' && uid !== '');
            if (!isLoggedIn) {
                if (typeof openSignup === 'function') {
                    openSignup();
                } else {
                    window.location.href = 'index.php?auth_required=1';
                }
                return;
            }

            currentPostId = postId;
            currentShopName = shopName;

            commentModalOverlay.style.display = 'flex';
            // Trigger flow
            setTimeout(() => {
                commentModalOverlay.style.opacity = '1';
                commentModal.style.transform = 'translateY(0)';
            }, 10);
            document.body.style.overflow = 'hidden';

            commentInput.placeholder = shopName ? `Reply to ${shopName}...` : `Add a comment...`;
            commentInput.value = '';

            // Show loading state
            commentsFeed.innerHTML = '<div style="text-align:center; padding: 40px; color: rgba(255,255,255,0.5);"><i class="fa-solid fa-circle-notch fa-spin fa-2x"></i></div>';

            let formData = new FormData();
            formData.append('PostID', postId);

            fetch('GetPostCommentsWeb.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(json => {
                    if (json.success && json.data && json.data.length > 0) {
                        let html = '';
                        json.data.forEach(c => {
                            let userName = (c.AuthorName || c.name || 'User');
                            let color = stringToColor(userName);
                            let photo = `https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=${color}&color=fff`;

                            if (c.UserPhoto && c.UserPhoto !== 'NONE' && c.UserPhoto.trim() !== '') {
                                if (c.UserPhoto.includes('http')) {
                                    photo = c.UserPhoto;
                                } else {
                                    let base = '<?= $DomainNamee ?? "https://qoon.app/dash/" ?>';
                                    photo = c.UserPhoto.startsWith('photo/') ? base + c.UserPhoto : base + 'photo/' + c.UserPhoto;
                                }
                            }
                            let text = (c.CommentText || '').replace(/\n/g, '<br>');

                            html += `
                            <div style="display: flex; gap: 12px; margin-bottom: 24px;">
                                <img src="${photo}" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=${color}&color=fff'">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: baseline; gap: 8px;">
                                        <span style="font-weight: 600; font-size: 14px; color: #fff;">${userName}</span>
                                        <span style="font-size: 12px; color: rgba(255,255,255,0.5);">${timeAgo(c.CreatedAtComments)}</span>
                                    </div>
                                    <p style="font-size: 14px; color: rgba(255,255,255,0.8); margin-top: 4px; line-height: 1.4; word-break: break-word;">${text}</p>
                                    <div style="display: flex; gap: 16px; margin-top: 8px;">
                                        <span style="font-size: 12px; color: rgba(255,255,255,0.5); font-weight: 500; cursor: pointer; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.5)'" onclick="setReply('${userName}')">Reply</span>
                                        <span style="font-size: 12px; color: rgba(255,255,255,0.5); font-weight: 500; cursor: pointer;"><i class="fa-regular fa-heart"></i> 0</span>
                                    </div>
                                </div>
                            </div>
                            `;
                        });
                        commentsFeed.innerHTML = html;
                    } else {
                        commentsFeed.innerHTML = '<div style="text-align:center; padding: 40px; color: rgba(255,255,255,0.5);">No comments yet. Be the first to comment!</div>';
                    }
                })
                .catch(err => {
                    commentsFeed.innerHTML = '<div style="text-align:center; padding: 40px; color: rgba(255,255,255,0.5);">Failed to load comments</div>';
                });
        }

        function setReply(name) {
            commentInput.placeholder = `Reply to ${name}...`;
            commentInput.focus();
        }

        function sendComment() {
            const text = commentInput.value.trim();
            const userId = getCookie('qoon_user_id');

            if (!userId || userId === '0' || userId === '') {
                alert('Please sign in to join the conversation!');
                openSignup();
                return;
            }

            if (!text || !currentPostId) return;

            const btn = document.querySelector('#comments-modal button i');
            const originalClass = btn.className;
            btn.className = 'fa-solid fa-circle-notch fa-spin';

            let formData = new FormData();
            formData.append('PostID', currentPostId);
            formData.append('CommentText', text);
            formData.append('UserID', userId);
            formData.append('ShopID', '0');

            fetch('AddComment.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(json => {
                    if (json.success) {
                        commentInput.value = '';
                        openCommentModal(currentPostId, currentShopName); // Refresh list
                    } else {
                        alert('Could not post comment. Please try again.');
                    }
                })
                .finally(() => {
                    btn.className = originalClass;
                });
        }

        function getCookie(name) {
            let match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
            if (match) return match[2];
            return null;
        }

        commentInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendComment();
            }
        });

        function closeCommentModal() {
            commentModalOverlay.style.opacity = '0';
            commentModal.style.transform = 'translateY(100%)';
            setTimeout(() => {
                commentModalOverlay.style.display = 'none';
                document.body.style.overflow = '';
            }, 400); // Wait for transition
        }

        // Close on outside click
        commentModalOverlay.addEventListener('click', (e) => {
            if (e.target === commentModalOverlay) {
                closeCommentModal();
            }
        });

</script>
