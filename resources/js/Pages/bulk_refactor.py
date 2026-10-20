import os
import re

files_to_process = [
    r'c:\Users\Daniel\Documents\2025 - Personnel\Projets\SUBNETCONGO\elearning\resources\js\Pages\Courses.vue',
    r'c:\Users\Daniel\Documents\2025 - Personnel\Projets\SUBNETCONGO\elearning\resources\js\Pages\LearningPaths.vue',
    r'c:\Users\Daniel\Documents\2025 - Personnel\Projets\SUBNETCONGO\elearning\resources\js\Pages\Certificates.vue'
]

configs_dir = r'c:\Users\Daniel\Documents\2025 - Personnel\Projets\SUBNETCONGO\elearning\resources\js\Pages\Configurations'
instructor_dir = r'c:\Users\Daniel\Documents\2025 - Personnel\Projets\SUBNETCONGO\elearning\resources\js\Pages\Instructor'

for root, _, files in os.walk(configs_dir):
    for f in files:
        if f.endswith('.vue'):
            files_to_process.append(os.path.join(root, f))

for root, _, files in os.walk(instructor_dir):
    for f in files:
        if f.endswith('.vue'):
            files_to_process.append(os.path.join(root, f))

def refactor_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Update AppLayout import
    content = re.sub(
        r'import AppLayout from [\'"]@/Layouts/AppLayout\.vue[\'"];',
        r'import AppLayout from "@/sakai/layout/AppLayout.vue";',
        content
    )
    # Check if there is any AppLayout import, if not add it but usually it is there.
    
    # 2. Replace Jetstream Components with PrimeVue
    content = re.sub(r'<TextInput\b', '<InputText', content)
    content = re.sub(r'</TextInput>', '</InputText>', content)
    content = re.sub(r'<PrimaryButton\b', '<Button', content)
    content = re.sub(r'</PrimaryButton>', '</Button>', content)
    content = re.sub(r'<SecondaryButton\b', '<Button class="p-button-secondary"', content)
    content = re.sub(r'</SecondaryButton>', '</Button>', content)
    content = re.sub(r'<InputLabel\b', '<label', content)
    content = re.sub(r'</InputLabel>', '</label>', content)
    content = re.sub(r'<InputError\b', '<small class="p-error"', content)
    content = re.sub(r'</InputError>', '</small>', content)

    # 3. Update Colors
    content = re.sub(r'\bindigo-(\d+)\b', r'blue-\1', content)
    content = re.sub(r'\bviolet-(\d+)\b', r'blue-\1', content)
    content = re.sub(r'\bsky-(\d+)\b', r'blue-\1', content)
    
    content = re.sub(r'\bemerald-(\d+)\b', r'red-\1', content)
    content = re.sub(r'\bamber-(\d+)\b', r'red-\1', content)
    content = re.sub(r'\brose-(\d+)\b', r'red-\1', content)
    
    # Update standard card designs to the V50 card design:
    # `bg-white rounded-xl border border-slate-200 shadow-sm p-6`
    # Replace common container classes like bg-white shadow-xl sm:rounded-lg
    content = re.sub(r'bg-white shadow(?:-xl|-lg|-md)? sm:rounded-lg', 'bg-white rounded-xl border border-slate-200 shadow-sm p-6', content)
    content = re.sub(r'bg-white overflow-hidden shadow(?:-xl|-lg|-md)? sm:rounded-lg', 'bg-white rounded-xl border border-slate-200 shadow-sm p-6 overflow-hidden', content)

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Refactored {filepath}")

for f in files_to_process:
    if os.path.exists(f):
        refactor_file(f)

print("All files processed.")
