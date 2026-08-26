<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Data</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 font-sans antialiased min-h-screen p-6">

    <div class="container mx-auto max-w-5xl mt-10">
        
        <!-- Global Alerts -->
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Upload Cards Container -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

            <!-- 1. STUDENT UPLOAD CARD -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <h2 class="text-2xl font-bold mb-6 text-gray-800">Upload Students List</h2>

                <form action="{{ route('students.import.process') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-6">
                        <label for="student_excel_file" class="block text-gray-700 font-semibold mb-2">
                            Select Excel File (.xlsx, .xls, .csv)
                        </label>
                        <input type="file" name="excel_file" id="student_excel_file" accept=".xlsx, .xls, .csv" required
                               class="w-full border border-gray-300 p-3 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition-colors">
                            Upload Students
                        </button>
                    </div>
                </form>
            </div>

            <!-- 2. GUARDIAN UPLOAD CARD -->
            <div class="bg-white rounded-lg shadow-md p-8 border-t-4 border-indigo-500 md:border-t-0 md:border-l-4">
                <h2 class="text-2xl font-bold mb-6 text-gray-800">Upload Guardians List</h2>

                <form action="{{ route('guardians.import.process') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-6">
                        <label for="guardian_excel_file" class="block text-gray-700 font-semibold mb-2">
                            Select Excel File (.xlsx, .xls, .csv)
                        </label>
                        <input type="file" name="excel_file" id="guardian_excel_file" accept=".xlsx, .xls, .csv" required
                               class="w-full border border-gray-300 p-3 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded shadow transition-colors">
                            Upload Guardians
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

</body>
</html>