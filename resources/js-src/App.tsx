import { useEffect } from 'react';
import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import { toast, Toaster } from 'sonner';
import { Catalogue } from './pages/Catalogue';
import { Taker } from './pages/Taker';
import { Leaderboard } from './pages/Leaderboard';
import { Results } from './pages/Results';
import { Manager } from './pages/admin/Manager';
import { Invitations } from './pages/admin/Invitations';

// A data router (rather than <BrowserRouter>/<Routes>) is required for useBlocker,
// which Manager uses to confirm before navigating away from an unsaved form.
const router = createBrowserRouter(
    [
        { path: '/', element: <Catalogue /> },
        { path: '/admin', element: <Manager /> },
        { path: '/admin/quizzes/:quizId/invitations', element: <Invitations /> },
        { path: '/:quizId/leaderboard', element: <Leaderboard /> },
        { path: '/:quizId/results', element: <Results /> },
        { path: '/:quizId', element: <Taker /> },
    ],
    { basename: window.QuizConfig.basePath },
);

export function App() {
    // Surfaces whatever the host redirected in with — lets a host page's
    // session()->flash('message', ...) (or ->flash('error', ...) / 'bulk_errors')
    // show up here too, the same way it would in the host's own Blade/Inertia views.
    useEffect(() => {
        const { flash } = window.QuizConfig;
        if (flash.message) toast.success(flash.message);
        if (flash.error) toast.error(flash.error);
        flash.bulk_errors?.forEach((message) => toast.error(message));
    }, []);

    return (
        <>
            <Toaster position="top-right" richColors />
            <RouterProvider router={router} />
        </>
    );
}
