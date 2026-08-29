<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::paginate(10);
        return Inertia::render('Students/Index', [
            'students' => $students
        ]);
    }

    public function create()
    {
        return Inertia::render('Students/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'nis' => 'required|string|max:25|unique:students',
            'email' => 'required|email|unique:users',
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->nis,
                'password' => Hash::make('password123'),
                'role' => 'student',
            ]);

            Student::create([
                'user_id' => $user->id,
                'nis' => $request->nis,
                'name' => $request->name,
                'gender' => $request->gender,
                'religion' => $request->religion ?? 'Islam',
            ]);
        });

        return redirect()->route('students.index')->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function edit(Student $student)
    {
        return Inertia::render('Students/Edit', [
            'student' => $student
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'nis' => 'required|string|max:25|unique:students,nis,' . $student->id,
        ]);

        DB::transaction(function () use ($request, $student) {
            $student->user->update([
                'name' => $request->name,
                'username' => $request->nis,
            ]);

            $student->update([
                'nis' => $request->nis,
                'name' => $request->name,
                'gender' => $request->gender,
                'religion' => $request->religion,
            ]);
        });

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student)
    {
        DB::transaction(function () use ($student) {
            $user = $student->user;
            $student->delete();
            if ($user) {
                $user->delete();
            }
        });

        return redirect()->route('students.index')->with('success', 'Siswa berhasil dihapus.');
    }
}
