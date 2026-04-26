with open('index.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

start_idx = 1397 # 1398 is index 1397
end_idx = 1665

with open('includes/modals/comments.php', 'w', encoding='utf-8') as f:
    f.writelines(lines[start_idx:end_idx])

new_lines = lines[:start_idx] + ["    <?php include 'includes/modals/comments.php'; ?>\n"] + lines[end_idx:]

with open('index.php', 'w', encoding='utf-8') as f:
    f.writelines(new_lines)
print('Comments extracted')
