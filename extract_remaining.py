with open('index.php', 'r', encoding='utf-8') as f:
    content = f.read()

import re

def extract_modal(html, start_str, filename):
    start_idx = html.find(start_str)
    if start_idx == -1:
        return html
    
    div_count = 0
    in_modal = False
    end_idx = -1
    
    pos = start_idx
    while pos < len(html):
        open_idx = html.find('<div', pos)
        close_idx = html.find('</div', pos)
        
        if open_idx == -1 and close_idx == -1:
            break
            
        if open_idx != -1 and (close_idx == -1 or open_idx < close_idx):
            div_count += 1
            in_modal = True
            pos = open_idx + 4
        elif close_idx != -1:
            div_count -= 1
            pos = close_idx + 6
            if in_modal and div_count == 0:
                end_idx = pos
                break

    if end_idx != -1:
        modal_html = html[start_idx:end_idx]
        remainder = html[end_idx:end_idx+300]
        if '<script>' in remainder:
            s_start = html.find('<script>', end_idx)
            if s_start < end_idx + 50:
                s_end = html.find('</script>', end_idx) + 9
                modal_html += html[end_idx:s_end]
                end_idx = s_end
        
        with open(f'includes/modals/{filename}', 'w', encoding='utf-8') as f:
            f.write(modal_html)
            
        return html[:start_idx] + f"<?php include 'includes/modals/{filename}'; ?>\n" + html[end_idx:]
    
    return html

content = extract_modal(content, '<div id="teleport-modal-overlay"', 'teleport.php')

with open('index.php', 'w', encoding='utf-8') as f:
    f.write(content)

print('teleport extracted')
