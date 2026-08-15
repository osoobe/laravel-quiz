import * as RadixTabs from '@radix-ui/react-tabs';
import { type ReactNode } from 'react';
import { cn } from '../../lib/cn';

export function Tabs({
    value,
    onValueChange,
    tabs,
    children,
}: {
    value: string;
    onValueChange: (value: string) => void;
    tabs: { value: string; label: string; icon?: ReactNode }[];
    children: ReactNode;
}) {
    return (
        <RadixTabs.Root value={value} onValueChange={onValueChange}>
            <RadixTabs.List className="flex flex-wrap gap-1 rounded-lg bg-gray-100 p-1" aria-label="Quiz admin sections">
                {tabs.map((tab) => (
                    <RadixTabs.Trigger
                        key={tab.value}
                        value={tab.value}
                        className={cn(
                            'flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium text-gray-600 transition-colors',
                            'data-[state=active]:bg-white data-[state=active]:text-gray-900 data-[state=active]:shadow-sm',
                        )}
                    >
                        {tab.icon}
                        {tab.label}
                    </RadixTabs.Trigger>
                ))}
            </RadixTabs.List>
            {children}
        </RadixTabs.Root>
    );
}

export const TabPanel = RadixTabs.Content;
