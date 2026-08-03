<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\StudentsImport;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class StudentImportController extends Controller
{
    /**
     * Display the upload view.
     */
    public function index()
    {
        return view('students.import');
    }

    /**
     * Handle the file upload and import process.
     */
    public function import(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,csv|max:10240', // Max 10MB
        ]);

        try {
            // Trigger the Laravel Excel import process
            Excel::import(new StudentsImport, $request->file('excel_file'));

            return redirect()->back()->with('success', 'Students successfully imported and linked to the latest School Year!');
            
        } catch (Exception $e) {
            // Catch our custom "No School Year" exception or generic database errors
            return redirect()->back()->withErrors(['error' => 'Import failed: ' . $e->getMessage()]);
        }
    }
}