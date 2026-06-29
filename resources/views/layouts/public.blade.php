<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Sales en marketing vacatures') | Sales en Marketing Vacatures</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>

<body class="font-inter antialiased bg-white text-slate-800 tracking-tight">

<!-- Page wrapper -->
<div class="flex flex-col min-h-screen overflow-hidden">

    <!-- Site header -->
@include('partials.header')
    <!-- Page content -->
@yield('content')
    <!-- Site footer -->
@include('partials.footer')
</div>



</body>

</html>
