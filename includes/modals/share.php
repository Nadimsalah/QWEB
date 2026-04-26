    <div id="share-modal-overlay" style="position: fixed; inset: 0; z-index: 99999; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); display: none; align-items: flex-end; justify-content: center; opacity: 0; transition: opacity 0.3s ease;">
        <div id="share-modal" style="width: 100%; max-width: 500px; padding: 24px 20px; background: #1a1a1a; border-top-left-radius: 28px; border-top-right-radius: 28px; border-top: 1px solid rgba(255,255,255,0.1); transform: translateY(100%); transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1); box-shadow: 0 -10px 40px rgba(0,0,0,0.8);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h3 style="font-size: 18px; font-weight: 700; color: #fff;">Share to</h3>
                <button onclick="closeShareModal()" style="width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.1); border: none; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">
                <!-- WhatsApp -->
                <button onclick="shareTo('whatsapp')" style="background: transparent; border: none; color: #fff; display: flex; flex-direction: column; align-items: center; cursor: pointer;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: #25D366; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 8px; box-shadow: 0 4px 12px rgba(37,211,102,0.4);"><i class="fa-brands fa-whatsapp"></i></div>
                    <span style="font-size: 12px; font-weight: 500;">WhatsApp</span>
                </button>
                <!-- Instagram (Copy Link representation) -->
                <button onclick="shareTo('instagram')" style="background: transparent; border: none; color: #fff; display: flex; flex-direction: column; align-items: center; cursor: pointer;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 8px; box-shadow: 0 4px 12px rgba(220,39,67,0.4);"><i class="fa-brands fa-instagram"></i></div>
                    <span style="font-size: 12px; font-weight: 500;">Instagram</span>
                </button>
                <!-- Facebook -->
                <button onclick="shareTo('facebook')" style="background: transparent; border: none; color: #fff; display: flex; flex-direction: column; align-items: center; cursor: pointer;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: #1877F2; display: flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 8px; box-shadow: 0 4px 12px rgba(24,119,242,0.4);"><i class="fa-brands fa-facebook-f"></i></div>
                    <span style="font-size: 12px; font-weight: 500;">Facebook</span>
                </button>
                <!-- Twitter -->
                <button onclick="shareTo('twitter')" style="background: transparent; border: none; color: #fff; display: flex; flex-direction: column; align-items: center; cursor: pointer;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: #000; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 8px; border: 1px solid rgba(255,255,255,0.2);"><i class="fa-brands fa-x-twitter"></i></div>
                    <span style="font-size: 12px; font-weight: 500;">X</span>
                </button>
            </div>
            
            <div style="width: 100%; height: 1px; background: rgba(255,255,255,0.1); margin-bottom: 16px;"></div>
            
            <button id="copy-link-btn" onclick="copyLink()" style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 16px; color: #fff; font-size: 15px; font-weight: 600; font-family: inherit; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: background 0.2s;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-link"></i></div>
                <span style="flex: 1; text-align: left;">Copy Link</span>
            </button>
        </div>
    </div>
