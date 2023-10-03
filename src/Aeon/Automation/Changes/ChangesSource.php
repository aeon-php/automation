<?php declare(strict_types=1);

namespace Aeon\Automation\Changes;

use Aeon\Automation\GitHub\Commit;
use Aeon\Automation\GitHub\PullRequest;
use Aeon\Calendar\Gregorian\DateTime;

final class ChangesSource
{
    public const TYPE_COMMIT = 'commit';

    public const TYPE_PULL_REQUEST = 'pull_request';

    private string $type;

    private string $id;

    private string $url;

    private string $title;

    private string $description;

    private DateTime $date;

    private string $user;

    private string $userUrl;

    public function __construct(
        string $type,
        string $id,
        string $url,
        string $title,
        string $description,
        DateTime $date,
        string $user,
        string $userUrl
    ) {
        if (!\in_array($type, [self::TYPE_COMMIT, self::TYPE_PULL_REQUEST], true)) {
            throw new \InvalidArgumentException('Invalid type: ' . $type);
        }

        if ($user === '1') {
            throw new \RuntimeException(\json_encode([
                'type' => $type,
                'id' => $id,
                'url' => $url,
                'title' => $title,
                'description' => $description,
                'date' => $date->format('Y-m-d H:i:s'),
                'user' => $user,
                'userUrl' => $userUrl,
            ]));
        }

        $this->type = $type;
        $this->id = $id;
        $this->url = $url;
        $this->title = $title;
        $this->date = $date;
        $this->description = $description;
        $this->user = $user;
        $this->userUrl = $userUrl;
    }

    public static function fromCommit(Commit $commit) : self
    {
        return new self(
            self::TYPE_COMMIT,
            $commit->sha(),
            $commit->url(),
            $commit->title(),
            $commit->description(),
            $commit->date(),
            $commit->user(),
            $commit->userUrl()
        );
    }

    public static function fromPullRequest(PullRequest $pullRequest) : self
    {
        return new self(
            self::TYPE_PULL_REQUEST,
            (string) $pullRequest->number(),
            $pullRequest->url(),
            $pullRequest->title(),
            $pullRequest->description(),
            $pullRequest->date(),
            $pullRequest->user(),
            $pullRequest->userUrl()
        );
    }

    public function isPullRequest() : bool
    {
        return $this->type === self::TYPE_PULL_REQUEST;
    }

    public function isCommit() : bool
    {
        return $this->type === self::TYPE_COMMIT;
    }

    public function type() : string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function id() : string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function url() : string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function title() : string
    {
        return $this->title;
    }

    /**
     * @return DateTime
     */
    public function date() : DateTime
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function description() : string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function user() : string
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function userUrl() : string
    {
        return $this->userUrl;
    }

    public function isFrom(string ...$users) : bool
    {
        foreach ($users as $user) {
            if ($user === $this->user) {
                return true;
            }
        }

        return false;
    }

    public function equals(self $source) : bool
    {
        return $this->type === $source->type
            && $this->id === $source->id;
    }

    public function contributor() : Contributor
    {
        return new Contributor($this->user, $this->userUrl);
    }
}
