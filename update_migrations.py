import os
import glob
import re

migrations_dir = 'v2/database/migrations/'
files = glob.glob(migrations_dir + '*.php')

schemas = {
    'create_users_table': '''
            \->id();
            \->string('name');
            \->string('email')->unique()->nullable();
            \->string('username')->unique();
            \->timestamp('email_verified_at')->nullable();
            \->string('password');
            \->enum('role', ['admin', 'teacher', 'student'])->default('student');
            \->rememberToken();
            \->timestamps();
''',
    'create_students_table': '''
            \->id();
            \->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            \->string('nis', 25)->unique();
            \->string('nisn', 20)->nullable();
            \->string('name', 150);
            \->string('phone', 20)->nullable();
            \->text('address')->nullable();
            \->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            \->string('gender', 10)->nullable();
            \->string('religion', 50)->default('Islam');
            \->timestamps();
''',
    'create_teachers_table': '''
            \->id();
            \->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            \->string('nip', 50)->unique()->nullable();
            \->string('name', 150);
            \->string('phone', 20)->nullable();
            \->text('address')->nullable();
            \->string('employment_status', 20)->nullable();
            \->boolean('is_bk')->default(false);
            \->timestamps();
''',
    'create_school_classes_table': '''
            \->id();
            \->string('name', 100);
            \->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            \->timestamps();
''',
    'create_subjects_table': '''
            \->id();
            \->string('name', 100);
            \->timestamps();
''',
    'create_schedules_table': '''
            \->id();
            \->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            \->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            \->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            \->string('day', 20);
            \->time('start_time');
            \->time('end_time');
            \->string('room', 80)->nullable();
            \->timestamps();
''',
    'create_attendances_table': '''
            \->id();
            \->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            \->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            \->date('date');
            \->string('status', 20)->default('present'); // present, absent, sick, permission
            \->text('notes')->nullable();
            \->timestamps();
''',
    'create_journals_table': '''
            \->id();
            \->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            \->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            \->date('date');
            \->text('material_covered')->nullable();
            \->text('notes')->nullable();
            \->timestamps();
'''
}

for filepath in files:
    filename = os.path.basename(filepath)
    content = open(filepath, 'r').read()
    
    for key, schema_code in schemas.items():
        if key in filename:
            # Replace empty Schema::create block with new schema
            pattern = r"(Schema::create\('.*?', function \(Blueprint \\) \{).*?(\}\);)"
            replacement = r"\g<1>\n" + schema_code + r"        \g<2>"
            new_content = re.sub(pattern, replacement, content, flags=re.DOTALL)
            open(filepath, 'w').write(new_content)
            print("Updated " + filename)

