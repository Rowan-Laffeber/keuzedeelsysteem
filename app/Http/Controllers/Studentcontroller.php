<?php

namespace App\Http\Controllers;

use App\Models\Student;

class StudentController extends Controller
{
    public function index()
    {
        // Just fetch students with their user relationship
        $students = Student::with('user')->get();

        return view('studentoverzicht', compact('students'));
    }
}
