#!/usr/bin/env python3
"""
One-off cleanup: remove trailing whitespace and collapse consecutive blank lines
in PHP/JS/CSS files. Delete after running.
"""
import os
import re

ROOT = os.path.dirname(os.path.abspath(__file__))
SKIP_DIRS = {'.git', 'vendor', 'node_modules', 'lib'}
EXTS = ('.php', '.js', '.css')


def main():
    changed = 0
    for dirpath, dirnames, filenames in os.walk(ROOT):
        dirnames[:] = [d for d in dirnames if d not in SKIP_DIRS]
        for fn in filenames:
            ext = os.path.splitext(fn)[1].lower()
            if ext not in EXTS:
                continue
            full = os.path.join(dirpath, fn)
            rel = os.path.relpath(full, ROOT)
            if rel in ('strip_comments.py', 'strip_comments.php', 'cleanup_blanks.py'):
                continue
            with open(full, 'r', encoding='utf-8') as f:
                src = f.read()

            # 1) Remove trailing whitespace on every line
            lines = src.split('\n')
            lines = [re.sub(r'[ \t]+$', '', line) for line in lines]
            new = '\n'.join(lines)

            # 2) Collapse 3+ consecutive blank lines into a single blank line
            new = re.sub(r'\n{3,}', '\n\n', new)

            # 3) Remove leading blank lines at the very start of the file
            new = new.lstrip('\n')

            # 4) Ensure file ends with exactly one newline
            new = new.rstrip('\n') + '\n'

            if new != src:
                with open(full, 'w', encoding='utf-8') as f:
                    f.write(new)
                print(f"Cleaned: {rel}")
                changed += 1
    print(f"Done. {changed} files cleaned.")


if __name__ == '__main__':
    main()