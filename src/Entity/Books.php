<?php

namespace App\Entity;

use App\Repository\BooksRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BooksRepository::class)]
class Books
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 500)]
    private ?string $content = null;

    #[ORM\Column(length: 255)]
    private ?string $author = null;

    #[ORM\Column]
    private ?bool $ispublished = null;

    #[ORM\OneToMany(mappedBy: 'book', targetEntity: BooksComment::class)]
    private Collection $user;

    public function __construct()
    {
        $this->user = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function isIsPublished(): ?bool
    {
        return $this->ispublished;
    }

    public function setIsPublished(bool $ispublished): self
    {
        $this->ispublished = $ispublished;

        return $this;
    }

    /**
     * @return Collection<int, BooksComment>
     */
    public function getUser(): Collection
    {
        return $this->user;
    }

    public function addUser(BooksComment $user): self
    {
        if (!$this->user->contains($user)) {
            $this->user->add($user);
            $user->setBook($this);
        }

        return $this;
    }

    public function removeUser(BooksComment $user): self
    {
        if ($this->user->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getBook() === $this) {
                $user->setBook(null);
            }
        }

        return $this;
    }
}
