# Select thumbnail and profile media independently

Status: Accepted

## Context

Dog list cards and the profile page have different display needs while drawing
from the same media library.

## Decision

A dog's library holds images and videos. Select at most one thumbnail and one
profile item, both belonging to that dog. A thumbnail must be an image; profile
media can be an image or video. The same image may serve both roles.

Selections are optional and independent. Uploading media does not select it.
Clearing a role keeps the file in the library.

## Consequences

Changing one role leaves the other intact. Deleting selected media removes its
selection without automatically choosing a replacement. Entity rules and
database constraints protect role ownership, type, and uniqueness.

## References

- [Selection rules](../../../../src/Entity/Dog.php)
- [Media types and uniqueness](../../../../src/Entity/DogMedia.php)
- [Selection and deletion scenarios](../../../../src/Application/DogMedia/DogMediaService.php)
