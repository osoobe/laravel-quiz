import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { App } from './App';
import './styles.css';

const root = document.getElementById('quiz-root');

if (!root) {
    throw new Error('#quiz-root element not found — the Blade shell view is missing its mount point.');
}

createRoot(root).render(
    <StrictMode>
        <App />
    </StrictMode>,
);
