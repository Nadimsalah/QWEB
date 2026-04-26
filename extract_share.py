with open('index.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

start_idx = 1712 # index for 1713
end_idx = 1750   # up to 1750

with open('includes/modals/share.php', 'w', encoding='utf-8') as f:
    f.writelines(lines[start_idx:end_idx])

new_lines = lines[:start_idx] + ["    <?php include 'includes/modals/share.php'; ?>\n"] + lines[end_idx:]

with open('index.php', 'w', encoding='utf-8') as f:
    f.writelines(new_lines)
print('Share extracted')
