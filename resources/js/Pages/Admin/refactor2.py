import os
import re

directories = [
    r"c:\Users\Daniel\Documents\2025 - Personnel\Projets\SUBNETCONGO\elearning\resources\js\Pages\Admin\ContactMessages",
    r"c:\Users\Daniel\Documents\2025 - Personnel\Projets\SUBNETCONGO\elearning\resources\js\Pages\Admin\EmailTemplates",
    r"c:\Users\Daniel\Documents\2025 - Personnel\Projets\SUBNETCONGO\elearning\resources\js\Pages\Admin\Emails",
    r"c:\Users\Daniel\Documents\2025 - Personnel\Projets\SUBNETCONGO\elearning\resources\js\Pages\Admin\Enrollments",
    r"c:\Users\Daniel\Documents\2025 - Personnel\Projets\SUBNETCONGO\elearning\resources\js\Pages\Admin\LegalContent",
    r"c:\Users\Daniel\Documents\2025 - Personnel\Projets\SUBNETCONGO\elearning\resources\js\Pages\Admin\LegalInfo"
]

def process_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Look for div class that starts with bg-white and has variations of rounding and padding and border
    # Examples to match:
    # bg-white rounded-3xl p-6 border border-slate-200 shadow-sm
    # bg-white rounded-[1rem] border border-slate-200/50 shadow-2xl
    # bg-white p-5 rounded-[1rem] border border-slate-200/60 shadow-sm
    
    # We will use a regex to match classes containing bg-white, and replace the whole class attribute if it looks like a card.
    # Actually, simpler: replace specific substrings.
    
    patterns = [
        r'bg-white rounded-3xl p-6 border border-slate-200 shadow-sm',
        r'bg-white rounded-3xl shadow-sm border border-slate-200 p-6',
        r'bg-white p-5 rounded-\[1rem\] border border-slate-200/60 shadow-sm',
        r'bg-white rounded-3xl p-5 border border-slate-200 shadow-sm',
        r'bg-white rounded-2xl shadow border border-slate-100 p-6',
        r'bg-white rounded-xl shadow-sm border border-slate-200 p-4'
    ]
    
    for p in patterns:
        content = re.sub(p, r'bg-white rounded-xl border border-slate-200 shadow-sm p-6', content)
        
    # Also for those with overflow-hidden
    patterns_overflow = [
        r'bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden',
        r'bg-white rounded-\[1rem\] border border-slate-200/50 shadow-2xl overflow-hidden',
        r'bg-white rounded-2xl shadow border border-slate-100 overflow-hidden',
        r'bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden'
    ]
    for p in patterns_overflow:
        content = re.sub(p, r'bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden', content)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

for d in directories:
    for root, _, files in os.walk(d):
        for file in files:
            if file.endswith('.vue'):
                process_file(os.path.join(root, file))
