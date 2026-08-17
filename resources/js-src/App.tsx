import { useEffect } from 'react';
import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { toast, Toaster } from 'sonner';
import { Catalogue } from './pages/Catalogue';
import { Taker } from './pages/Taker';
import { Leaderboard } from './pages/Leaderboard';
import { Results } from './pages/Results';
import { Manager } from './pages/admin/Manager';
import { Invitations } from './pages/admin/Invitations';

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
        <BrowserRouter basename={window.QuizConfig.basePath}>
            <Toaster position="top-right" richColors />
            <Routes>
                <Route path="/" element={<Catalogue />} />
                <Route path="/admin" element={<Manager />} />
                <Route path="/admin/quizzes/:quizId/invitations" element={<Invitations />} />
                <Route path="/:quizId/leaderboard" element={<Leaderboard />} />
                <Route path="/:quizId/results" element={<Results />} />
                <Route path="/:quizId" element={<Taker />} />
            </Routes>
        </BrowserRouter>
    );
}
