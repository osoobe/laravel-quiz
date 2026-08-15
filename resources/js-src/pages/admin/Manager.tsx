import { useState } from 'react';
import { FileQuestion, Folder, ListChecks, Tag } from 'lucide-react';
import { Tabs, TabPanel } from '../../components/ui/Tabs';
import { QuestionsTab } from './QuestionsTab';
import { QuizzesTab } from './QuizzesTab';
import { TopicsTab } from './TopicsTab';
import { CategoriesTab } from './CategoriesTab';

const TABS = [
    { value: 'questions', label: 'Questions', icon: <FileQuestion className="h-4 w-4" aria-hidden /> },
    { value: 'quizzes', label: 'Quizzes', icon: <ListChecks className="h-4 w-4" aria-hidden /> },
    { value: 'topics', label: 'Topics', icon: <Folder className="h-4 w-4" aria-hidden /> },
    { value: 'categories', label: 'Categories', icon: <Tag className="h-4 w-4" aria-hidden /> },
];

export function Manager() {
    const [tab, setTab] = useState('questions');

    return (
        <div className="mx-auto max-w-5xl px-4 py-10">
            <h1 className="text-2xl font-bold text-gray-900">Quiz Manager</h1>

            <div className="mt-6">
                <Tabs value={tab} onValueChange={setTab} tabs={TABS}>
                    <TabPanel value="questions">
                        <QuestionsTab />
                    </TabPanel>
                    <TabPanel value="quizzes">
                        <QuizzesTab />
                    </TabPanel>
                    <TabPanel value="topics">
                        <TopicsTab />
                    </TabPanel>
                    <TabPanel value="categories">
                        <CategoriesTab />
                    </TabPanel>
                </Tabs>
            </div>
        </div>
    );
}
