with open('index.php', 'r', encoding='utf-8') as f:
    content = f.read()

import os

for modal in ['checkout.php', 'share.php', 'product.php', 'auth.php', 'comments.php', 'teleport.php']:
    if os.path.exists(f'includes/modals/{modal}'):
        with open(f'includes/modals/{modal}', 'r', encoding='utf-8') as mf:
            modal_content = mf.read()
        content = content.replace(f"<?php include 'includes/modals/{modal}'; ?>\n", modal_content)

with open('index.php', 'w', encoding='utf-8') as f:
    f.write(content)
print("Restored index.php")
