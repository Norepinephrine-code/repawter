#!/usr/bin/env python3
"""
One-off script: strip all comments from PHP/JS/CSS files.
Delete after running.
"""
import os
import re
import sys

ROOT = os.path.dirname(os.path.abspath(__file__))
SKIP_DIRS = {'.git', 'vendor', 'node_modules', 'lib'}
SKIP_PREFIX = ('.htaccess',)


def should_skip(rel_path: str) -> bool:
    parts = rel_path.replace('\\', '/').split('/')
    for p in parts:
        if p in SKIP_DIRS:
            return True
    name = os.path.basename(rel_path)
    if name.startswith(SKIP_PREFIX):
        return True
    if rel_path == 'strip_comments.py' or rel_path == 'strip_comments.php':
        return True
    return False


def collect_files():
    php_files = []
    js_css_files = []
    for dirpath, dirnames, filenames in os.walk(ROOT):
        dirnames[:] = [d for d in dirnames if d not in SKIP_DIRS]
        for fn in filenames:
            full = os.path.join(dirpath, fn)
            rel = os.path.relpath(full, ROOT)
            if should_skip(rel):
                continue
            ext = os.path.splitext(fn)[1].lower()
            if ext == '.php':
                php_files.append(full)
            elif ext in ('.js', '.css'):
                js_css_files.append(full)
    return php_files, js_css_files


def strip_php(src: str) -> str:
    """Remove //, #, and /* */ comments from PHP source while preserving strings."""
    out = []
    i = 0
    n = len(src)
    while i < n:
        ch = src[i]
        # Single-quoted string
        if ch == "'":
            out.append(ch)
            i += 1
            while i < n:
                c = src[i]
                out.append(c)
                if c == '\\' and i + 1 < n:
                    out.append(src[i + 1])
                    i += 2
                    continue
                if c == "'":
                    i += 1
                    break
                i += 1
            continue
        # Double-quoted string
        if ch == '"':
            out.append(ch)
            i += 1
            while i < n:
                c = src[i]
                out.append(c)
                if c == '\\' and i + 1 < n:
                    out.append(src[i + 1])
                    i += 2
                    continue
                if c == '"':
                    i += 1
                    break
                i += 1
            continue
        # Heredoc / Nowdoc: <<<LABEL ... LABEL;
        if ch == '<' and src[i:i+3] == '<<<':
            # Find the label
            j = i + 3
            while j < n and src[j] in ' \t':
                j += 1
            quote = None
            if j < n and src[j] in ('"', "'"):
                quote = src[j]
                j += 1
            label_start = j
            while j < n and src[j].isalnum() or (j < n and src[j] == '_'):
                j += 1
            label = src[label_start:j]
            if quote and j < n and src[j] == quote:
                j += 1
            # Skip to end of line
            while j < n and src[j] != '\n':
                j += 1
            if j < n:
                out.append(src[i:j+1])
                i = j + 1
            else:
                out.append(src[i:j])
                i = j
            # Now consume until closing label
            while i < n:
                # Check for label at start of line (with optional whitespace)
                k = i
                while k < n and src[k] in ' \t':
                    k += 1
                if src[k:k+len(label)] == label:
                    after = k + len(label)
                    if after < n and (src[after] == ';' or src[after] == ',' or src[after] == ')' or src[after] == '\n' or src[after] == ' '):
                        out.append(src[i:after])
                        i = after
                        break
                out.append(src[i])
                i += 1
            continue
        # Line comment: // or #
        if ch == '/' and i + 1 < n and src[i+1] == '/':
            # Skip to end of line, preserve newline
            j = i
            while j < n and src[j] != '\n':
                j += 1
            i = j
            continue
        if ch == '#':
            # # comment (PHP). But avoid #![...] shebang in JS — this is PHP only.
            j = i
            while j < n and src[j] != '\n':
                j += 1
            i = j
            continue
        # Block comment: /* ... */
        if ch == '/' and i + 1 < n and src[i+1] == '*':
            j = i + 2
            while j < n - 1 and not (src[j] == '*' and src[j+1] == '/'):
                j += 1
            j += 2
            if j > n:
                j = n
            # Preserve trailing newline if the comment ended on its own line
            if j < n and src[j] == '\n':
                out.append('\n')
                j += 1
            i = j
            continue
        out.append(ch)
        i += 1
    return ''.join(out)


def strip_js_css(src: str, is_js: bool) -> str:
    """Remove comments from JS/CSS. JS supports // and /* */; CSS only /* */."""
    out = []
    i = 0
    n = len(src)
    while i < n:
        ch = src[i]
        # Single-quoted string (JS)
        if ch == "'" and is_js:
            out.append(ch)
            i += 1
            while i < n:
                c = src[i]
                out.append(c)
                if c == '\\' and i + 1 < n:
                    out.append(src[i + 1])
                    i += 2
                    continue
                if c == "'":
                    i += 1
                    break
                i += 1
            continue
        # Double-quoted string
        if ch == '"' and is_js:
            out.append(ch)
            i += 1
            while i < n:
                c = src[i]
                out.append(c)
                if c == '\\' and i + 1 < n:
                    out.append(src[i + 1])
                    i += 2
                    continue
                if c == '"':
                    i += 1
                    break
                i += 1
            continue
        # Template literal (JS, backticks)
        if ch == '`' and is_js:
            out.append(ch)
            i += 1
            while i < n:
                c = src[i]
                out.append(c)
                if c == '\\' and i + 1 < n:
                    out.append(src[i + 1])
                    i += 2
                    continue
                if c == '`':
                    i += 1
                    break
                i += 1
            continue
        # Line comment: //
        if ch == '/' and i + 1 < n and src[i+1] == '/' and is_js:
            j = i
            while j < n and src[j] != '\n':
                j += 1
            i = j
            continue
        # Block comment: /* ... */
        if ch == '/' and i + 1 < n and src[i+1] == '*':
            j = i + 2
            while j < n - 1 and not (src[j] == '*' and src[j+1] == '/'):
                j += 1
            j += 2
            if j > n:
                j = n
            if j < n and src[j] == '\n':
                out.append('\n')
                j += 1
            i = j
            continue
        out.append(ch)
        i += 1
    return ''.join(out)


def cleanup_blank_lines(text: str) -> str:
    # Collapse 3+ consecutive newlines into 2
    return re.sub(r'\n{3,}', '\n\n', text)


def main():
    php_files, js_css_files = collect_files()
    changed = 0
    for path in php_files:
        with open(path, 'r', encoding='utf-8') as f:
            src = f.read()
        new = strip_php(src)
        new = cleanup_blank_lines(new)
        if new != src:
            with open(path, 'w', encoding='utf-8') as f:
                f.write(new)
            print(f"PHP: {os.path.relpath(path, ROOT)}")
            changed += 1
    for path in js_css_files:
        with open(path, 'r', encoding='utf-8') as f:
            src = f.read()
        is_js = path.endswith('.js')
        new = strip_js_css(src, is_js)
        new = cleanup_blank_lines(new)
        if new != src:
            with open(path, 'w', encoding='utf-8') as f:
                f.write(new)
            print(f"{'JS' if is_js else 'CSS'}: {os.path.relpath(path, ROOT)}")
            changed += 1
    print(f"Done. {changed} files changed.")


if __name__ == '__main__':
    main()