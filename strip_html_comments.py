#!/usr/bin/env python3
"""
One-off script: remove HTML comments (<!-- ... -->) from PHP files,
and remove // comments inside JS heredoc blocks in PHP files.
Delete after running.
"""
import os
import re

ROOT = os.path.dirname(os.path.abspath(__file__))
SKIP_DIRS = {'.git', 'vendor', 'node_modules', 'lib'}


def remove_html_comments(src: str) -> str:
    """Remove HTML comments while preserving PHP code inside them."""
    result = []
    i = 0
    n = len(src)
    while i < n:
        if src[i:i+4] == '<!--':
            j = src.find('-->', i + 4)
            if j == -1:
                result.append(src[i:])
                break
            i = j + 3
            if i < n and src[i] == '\n':
                result.append('\n')
                i += 1
            continue
        result.append(src[i])
        i += 1
    return ''.join(result)


def remove_js_line_comments_in_heredocs(src: str) -> str:
    """Remove // line comments inside PHP heredoc blocks that contain JS."""
    result = []
    i = 0
    n = len(src)
    while i < n:
        if src[i:i+3] == '<<<':
            j = i + 3
            while j < n and src[j] in ' \t':
                j += 1
            quote = None
            if j < n and src[j] in ('"', "'"):
                quote = src[j]
                j += 1
            label_start = j
            while j < n and (src[j].isalnum() or src[j] == '_'):
                j += 1
            label = src[label_start:j]
            if quote and j < n and src[j] == quote:
                j += 1
            while j < n and src[j] != '\n':
                j += 1
            if j < n:
                result.append(src[i:j+1])
                i = j + 1
            else:
                result.append(src[i:j])
                i = j
            while i < n:
                k = i
                while k < n and src[k] in ' \t':
                    k += 1
                if src[k:k+len(label)] == label:
                    after = k + len(label)
                    if after < n and src[after] in (';', ',', ')', '\n', ' '):
                        heredoc_body = src[i:after]
                        lines = heredoc_body.split('\n')
                        cleaned = []
                        for line in lines:
                            stripped = line.lstrip()
                            if stripped.startswith('//'):
                                cleaned.append('')
                            else:
                                cleaned.append(line)
                        result.append('\n'.join(cleaned))
                        i = after
                        break
                result.append(src[i])
                i += 1
            continue
        result.append(src[i])
        i += 1
    return ''.join(result)


def cleanup_blank_lines(text: str) -> str:
    text = re.sub(r'[ \t]+$', '', text, flags=re.MULTILINE)
    text = re.sub(r'\n{3,}', '\n\n', text)
    text = text.lstrip('\n')
    text = text.rstrip('\n') + '\n'
    return text


def main():
    changed = 0
    for dirpath, dirnames, filenames in os.walk(ROOT):
        dirnames[:] = [d for d in dirnames if d not in SKIP_DIRS]
        for fn in filenames:
            if not fn.endswith('.php'):
                continue
            full = os.path.join(dirpath, fn)
            rel = os.path.relpath(full, ROOT)
            if rel in ('strip_comments.py', 'strip_comments.php', 'cleanup_blanks.py', 'strip_html_comments.py'):
                continue
            with open(full, 'r', encoding='utf-8') as f:
                src = f.read()
            new = remove_html_comments(src)
            new = remove_js_line_comments_in_heredocs(new)
            new = cleanup_blank_lines(new)
            if new != src:
                with open(full, 'w', encoding='utf-8') as f:
                    f.write(new)
                print(f"Cleaned: {rel}")
                changed += 1
    print(f"Done. {changed} files changed.")


if __name__ == '__main__':
    main()