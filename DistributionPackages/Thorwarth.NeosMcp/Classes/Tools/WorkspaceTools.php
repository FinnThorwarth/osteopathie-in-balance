<?php
declare(strict_types=1);

namespace Thorwarth\NeosMcp\Tools;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceStatus;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\WorkspacePublishingService;
use Thorwarth\NeosMcp\Protocol\InvalidParamsException;
use Thorwarth\NeosMcp\Service\ContentAccess;
use Thorwarth\NeosMcp\Service\NodeSerializer;

/**
 * Workspace-Tools: Status, Veröffentlichen, Verwerfen
 *
 * @Flow\Scope("singleton")
 */
class WorkspaceTools
{
    #[Flow\Inject]
    protected ContentAccess $access;

    #[Flow\Inject]
    protected NodeSerializer $serializer;

    #[Flow\Inject]
    protected WorkspacePublishingService $workspacePublishingService;

    public function status(array $args): array
    {
        $workspaceName = $this->access->resolveWorkspace(Args::str($args, 'workspace'), false);
        $workspace = $this->access->workspace($workspaceName);
        $items = [];
        if (!$workspace->isRootWorkspace()) {
            $subgraph = $this->access->subgraph($workspaceName, true);
            $changes = $this->workspacePublishingService->pendingWorkspaceChanges($this->access->contentRepositoryId(), $workspaceName);
            foreach ($changes as $change) {
                $item = [
                    'id' => $change->nodeAggregateId->value,
                    'created' => $change->created,
                    'changed' => $change->changed,
                    'moved' => $change->moved,
                    'deleted' => $change->deleted,
                ];
                $node = $subgraph->findNodeById($change->nodeAggregateId);
                if ($node !== null) {
                    $item['nodeType'] = $node->nodeTypeName->value;
                    $item['label'] = $this->serializer->label($node);
                    $document = $this->serializer->isDocument($node) ? $node : $this->serializer->closestDocument($node, $subgraph);
                    if ($document !== null) {
                        $item['document'] = $this->serializer->documentRef($document, $subgraph);
                    }
                }
                $items[] = $item;
            }
        }
        return [
            'workspace' => $workspaceName->value,
            'baseWorkspace' => $workspace->baseWorkspaceName?->value,
            'status' => $workspace->status->value,
            'outdated' => $workspace->status === WorkspaceStatus::OUTDATED,
            'pendingChanges' => count($items),
            'changes' => $items,
        ];
    }

    public function publish(array $args): array
    {
        $workspaceName = $this->access->resolveWorkspace(Args::str($args, 'workspace'), false);
        if ($workspaceName->isLive()) {
            throw new InvalidParamsException('Der Live-Workspace kann nicht veröffentlicht werden.');
        }
        $rebased = $this->access->rebaseIfOutdated($workspaceName);
        $contentRepositoryId = $this->access->contentRepositoryId();
        $documentId = Args::str($args, 'documentId');
        if ($documentId !== '') {
            try {
                $result = $this->workspacePublishingService->publishChangesInDocument($contentRepositoryId, $workspaceName, $this->nodeAggregateId($documentId));
            } catch (\Throwable $e) {
                throw new \RuntimeException(sprintf(
                    'Die Änderungen des Dokuments %s konnten nicht separat veröffentlicht werden (vermutlich Abhängigkeiten zu Änderungen in anderen Dokumenten, z. B. verschobene Inhalte): %s – Alternative: "publish" ohne documentId veröffentlicht alle Änderungen gemeinsam.',
                    $documentId,
                    $e->getMessage()
                ), 0, $e);
            }
        } else {
            $result = $this->workspacePublishingService->publishWorkspace($contentRepositoryId, $workspaceName);
        }
        return [
            'ok' => true,
            'workspace' => $workspaceName->value,
            'targetWorkspace' => $result->targetWorkspaceName->value,
            'publishedChanges' => $result->numberOfPublishedChanges,
            'rebased' => $rebased,
            'scope' => $documentId !== '' ? 'document ' . $documentId : 'all',
        ];
    }

    public function discard(array $args): array
    {
        $workspaceName = $this->access->resolveWorkspace(Args::str($args, 'workspace'), false);
        if ($workspaceName->isLive()) {
            throw new InvalidParamsException('Im Live-Workspace gibt es nichts zu verwerfen.');
        }
        $contentRepositoryId = $this->access->contentRepositoryId();
        $documentId = Args::str($args, 'documentId');
        if ($documentId !== '') {
            try {
                $result = $this->workspacePublishingService->discardChangesInDocument($contentRepositoryId, $workspaceName, $this->nodeAggregateId($documentId));
            } catch (\Throwable $e) {
                throw new \RuntimeException(sprintf(
                    'Die Änderungen des Dokuments %s konnten nicht separat verworfen werden (vermutlich Abhängigkeiten zu Änderungen in anderen Dokumenten): %s – Alternative: "discard" ohne documentId verwirft alle Änderungen.',
                    $documentId,
                    $e->getMessage()
                ), 0, $e);
            }
        } else {
            $result = $this->workspacePublishingService->discardAllWorkspaceChanges($contentRepositoryId, $workspaceName);
        }
        return [
            'ok' => true,
            'workspace' => $workspaceName->value,
            'discardedChanges' => $result->numberOfDiscardedChanges,
            'scope' => $documentId !== '' ? 'document ' . $documentId : 'all',
        ];
    }

    private function nodeAggregateId(string $id): NodeAggregateId
    {
        try {
            return NodeAggregateId::fromString($id);
        } catch (\Throwable $e) {
            throw new InvalidParamsException(sprintf('Ungültige Node-ID "%s".', $id), 0, $e);
        }
    }
}
