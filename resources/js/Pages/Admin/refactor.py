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

    # Ensure AppLayout from sakai
    if 'import AppLayout' not in content:
        content = re.sub(
            r"(import .* from '@inertiajs/vue3';)",
            r"\1\nimport AppLayout from '@/sakai/layout/AppLayout.vue';",
            content
        )
        # Also replace <AdminLayout> or whatever layout they use with <AppLayout>
        content = re.sub(r'<AdminLayout', r'<AppLayout', content)
        content = re.sub(r'</AdminLayout>', r'</AppLayout>', content)
        content = re.sub(r'import AdminLayout from .*;\n', '', content)

    # Replace legacy Jetstream components if they existed
    content = re.sub(r'<TextInput', r'<InputText', content)
    content = re.sub(r'</TextInput>', r'</InputText>', content)
    content = re.sub(r'import TextInput from .*;\n', 'import InputText from \'primevue/inputtext\';\n', content)
    
    content = re.sub(r'<PrimaryButton', r'<Button', content)
    content = re.sub(r'</PrimaryButton>', r'</Button>', content)
    content = re.sub(r'import PrimaryButton from .*;\n', 'import Button from \'primevue/button\';\n', content)

    # Colors substitution
    content = re.sub(r'\bindigo-(\d+)\b', r'blue-\1', content)
    content = re.sub(r'\bviolet-(\d+)\b', r'blue-\1', content)
    content = re.sub(r'\bemerald-(\d+)\b', r'blue-\1', content)
    
    # Replace rose and amber with red to enforce dual-color brand
    content = re.sub(r'\brose-(\d+)\b', r'red-\1', content)
    content = re.sub(r'\bamber-(\d+)\b', r'red-\1', content)
    
    # Specifically for #FF3B3B
    content = re.sub(r'#FF3B3B', r'#EF4444', content) # red-500 equivalent, or leave it. Actually #FF3B3B is pretty red.

    # Card styles
    # Find bg-white classes that represent cards and standardise them
    # "bg-white rounded-xl border border-slate-200 shadow-sm p-6"
    
    # Try to find common card variations and replace them with the standard one
    content = re.sub(
        r'bg-white\s+rounded-xl\s+p-5\s+shadow-sm\s+border\s+border-slate-100',
        r'bg-white rounded-xl border border-slate-200 shadow-sm p-6',
        content
    )
    content = re.sub(
        r'bg-white\s+rounded-xl\s+p-6\s+border\s+border-slate-200\s+shadow-sm',
        r'bg-white rounded-xl border border-slate-200 shadow-sm p-6',
        content
    )
    content = re.sub(
        r'bg-white\s+rounded-xl\s+shadow-sm\s+border\s+border-slate-200\s+overflow-hidden',
        r'bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden',
        content
    )

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

for d in directories:
    for root, _, files in os.walk(d):
        for file in files:
            if file.endswith('.vue'):
                process_file(os.path.join(root, file))
