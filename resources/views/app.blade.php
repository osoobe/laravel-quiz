<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quizzes</title>
    <meta name="csrf-token" content="{{ $bootstrap['csrfToken'] }}">
    @foreach ($quizCss as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach
</head>
<body>
    <div id="quiz-root"></div>
    <script>window.QuizConfig = {!! \Illuminate\Support\Js::from($bootstrap) !!};</script>
    @if ($quizJs)
        <script type="module" src="{{ $quizJs }}"></script>
    @else
        <p style="font-family: sans-serif; padding: 2rem;">
            The quiz frontend has not been built yet. Run <code>npm install &amp;&amp; npm run build</code>
            inside the osoobe/laravel-quiz package.
        </p>
    @endif
</body>
</html>
