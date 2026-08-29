<?php
$migrationsDir = __DIR__ . '/v2/database/migrations/';
$files = glob($migrationsDir . '*.php');

$schemas = [
    'create_users_table' => <<<PHP
            \$table->id();
            \$table->string('name');
            \$table->string('email')->unique()->nullable();
            \$table->string('username')->unique();
            \$table->timestamp('email_verified_at')->nullable();
            \$table->string('password');
            \$table->enum('role', ['admin', 'teacher', 'student'])->default('student');
            \$table->rememberToken();
            \$table->timestamps();
PHP,
    'create_students_table' => <<<PHP
            \$table->id();
            \$table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            \$table->string('nis', 25)->unique();
            \$table->string('nisn', 20)->nullable();
            \$table->string('name', 150);
            \$table->string('phone', 20)->nullable();
            \$table->text('address')->nullable();
            \$table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            \$table->string('gender', 10)->nullable();
            \$table->string('religion', 50)->default('Islam');
            \$table->timestamps();
PHP,
    'create_teachers_table' => <<<PHP
            \$table->id();
            \$table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            \$table->string('nip', 50)->unique()->nullable();
            \$table->string('name', 150);
            \$table->string('phone', 20)->nullable();
            \$table->text('address')->nullable();
            \$table->string('employment_status', 20)->nullable();
            \$table->boolean('is_bk')->default(false);
            \$table->timestamps();
PHP,
    'create_school_classes_table' => <<<PHP
            \$table->id();
            \$table->string('name', 100);
            \$table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            \$table->timestamps();
PHP,
    'create_subjects_table' => <<<PHP
            \$table->id();
            \$table->string('name', 100);
            \$table->timestamps();
PHP,
    'create_schedules_table' => <<<PHP
            \$table->id();
            \$table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            \$table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            \$table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            \$table->string('day', 20);
            \$table->time('start_time');
            \$table->time('end_time');
            \$table->string('room', 80)->nullable();
            \$table->timestamps();
PHP,
    'create_attendances_table' => <<<PHP
            \$table->id();
            \$table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            \$table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            \$table->date('date');
            \$table->string('status', 20)->default('present');
            \$table->text('notes')->nullable();
            \$table->timestamps();
PHP,
    'create_journals_table' => <<<PHP
            \$table->id();
            \$table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            \$table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            \$table->date('date');
            \$table->text('material_covered')->nullable();
            \$table->text('notes')->nullable();
            \$table->timestamps();
PHP,
];

foreach ($files as $file) {
    $filename = basename($file);
    $content = file_get_contents($file);
    
    foreach ($schemas as $key => $schema) {
        if (strpos($filename, $key) !== false) {
            // Find Schema::create block
            $pattern = '/(Schema::create\([\s\S]*?function\s*\(\s*Blueprint\s*\$table\s*\)\s*\{)([\s\S]*?)(\}\);)/';
            $replacement = "$1\n" . $schema . "\n        $3";
            $newContent = preg_replace($pattern, $replacement, $content);
            file_put_contents($file, $newContent);
            echo "Updated $filename\n";
        }
    }
}
