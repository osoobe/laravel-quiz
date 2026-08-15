import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { toast } from 'sonner';
import { UserPlus, Users, X } from 'lucide-react';
import { AdminInvitationApi } from '../../api/quiz';
import { ApiError } from '../../api/client';
import { Card } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { EmptyState } from '../../components/EmptyState';
import type { Invitation } from '../../api/types';

export function Invitations() {
    const { quizId } = useParams<{ quizId: string }>();
    const [invitations, setInvitations] = useState<Invitation[] | null>(null);
    const [single, setSingle] = useState('');
    const [bulk, setBulk] = useState('');

    function load() {
        if (!quizId) return;
        AdminInvitationApi.index(quizId)
            .then(setInvitations)
            .catch((error) => {
                toast.error(error instanceof ApiError ? error.message : 'Failed to load invitations');
                setInvitations([]);
            });
    }

    useEffect(load, [quizId]);

    async function invite(identifiers: string) {
        if (!quizId || !identifiers.trim()) return;

        const summary = await AdminInvitationApi.invite(quizId, identifiers);
        toast.success(
            `${summary.invited} invited, ${summary.already_invited} already invited, ${summary.not_found} not found${
                summary.failed ? `, ${summary.failed} failed` : ''
            }`,
        );
        load();
    }

    return (
        <div className="mx-auto max-w-2xl px-4 py-10">
            <h1 className="text-2xl font-bold text-gray-900">Invitations</h1>

            <Card className="mt-6 space-y-3">
                <h2 className="font-semibold text-gray-900">Add by email or @username</h2>
                <div className="flex gap-2">
                    <input
                        value={single}
                        onChange={(e) => setSingle(e.target.value)}
                        placeholder="name@example.com or @username"
                        className="flex-1 rounded-lg border border-quiz-border px-3 py-2 text-sm focus:border-quiz-primary focus:outline-none"
                    />
                    <Button
                        onClick={async () => {
                            await invite(single);
                            setSingle('');
                        }}
                    >
                        <UserPlus className="h-4 w-4" aria-hidden /> Add
                    </Button>
                </div>
            </Card>

            <Card className="mt-4 space-y-3">
                <h2 className="font-semibold text-gray-900">Bulk add</h2>
                <textarea
                    value={bulk}
                    onChange={(e) => setBulk(e.target.value)}
                    placeholder="Paste emails separated by commas, semicolons, spaces, or newlines"
                    rows={4}
                    className="w-full rounded-lg border border-quiz-border px-3 py-2 text-sm focus:border-quiz-primary focus:outline-none"
                />
                <Button
                    onClick={async () => {
                        await invite(bulk);
                        setBulk('');
                    }}
                >
                    Invite All
                </Button>
            </Card>

            <Card className="mt-4">
                <h2 className="mb-3 font-semibold text-gray-900">Invitees</h2>
                {invitations === null ? null : invitations.length === 0 ? (
                    <EmptyState icon={<Users className="h-10 w-10" />} title="No invitations yet" />
                ) : (
                    <ul className="divide-y divide-quiz-border">
                        {invitations.map((invitation) => (
                            <li key={invitation.id} className="flex items-center justify-between py-2.5">
                                <div>
                                    <p className="text-sm font-medium text-gray-900">{invitation.user.name}</p>
                                    <p className="text-xs text-quiz-muted">
                                        {invitation.user.email} · invited {new Date(invitation.invited_at).toLocaleDateString()}
                                    </p>
                                </div>
                                <button
                                    onClick={async () => {
                                        if (!quizId) return;
                                        await AdminInvitationApi.remove(quizId, invitation.id);
                                        load();
                                    }}
                                    aria-label={`Remove ${invitation.user.name}`}
                                    className="rounded p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-500"
                                >
                                    <X className="h-4 w-4" aria-hidden />
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>
        </div>
    );
}
