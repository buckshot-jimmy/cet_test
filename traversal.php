<?php

class Node
{
    public int $value;
    public ?Node $left = null;
    public ?Node $right = null;

    public function __construct(int $value)
    {
        $this->value = $value;
    }
}

class BinarySearchTree
{
    private ?Node $root;

    public function __construct($root = null) {
        $this->root = $root;
    }

    public function insert(int $value): void
    {
        $newNode = new Node($value);

        if ($this->root === null) {
            $this->root = $newNode;
            return;
        }

        $currentNode = $this->root;

        while (true) {
            if ($value < $currentNode->value) {
                if ($currentNode->left === null) {
                    $currentNode->left = $newNode;
                    return;
                }
                $currentNode = $currentNode->left;
            } else {
                if ($currentNode->right === null) {
                    $currentNode->right = $newNode;
                    return;
                }
                $currentNode = $currentNode->right;
            }
        }
    }

    public function search($node, $value, $parent = null)
    {
        if ($node === null) {
            echo "$value not found"; // not found
            return $node;
        }

        if ($value == $node->value) {
            echo "$value found with parent $parent->value"; // found
            return $node;
        }

        if ($value < $node->value) {
            return $this->search($node->left, $value, $node);
        } else {
            return $this->search($node->right, $value, $node);
        }
    }

    public function searchIterative($root, $value)
    {
        $parent = null;
        $currentNode = $root;

        while ($currentNode !== null) {
            if ($value == $currentNode->value) {
                echo "$currentNode->value found with parent $parent->value"; // found
                return 'found';
            }

            $parent = $currentNode;

            if ($value < $currentNode->value) {
                $currentNode = $currentNode->left;
            } else {
                $currentNode = $currentNode->right;
            }
        }

        return "$value not found"; // not found
    }

    public function delete($value)
    {
        $this->root = $this->deleteNode($this->root, $value);
    }

    private function deleteNode($node, $value)
    {
        if ($node === null) {
            return null;
        }

        // STEP 1: Find node
        if ($value < $node->value) {
            $node->left = $this->deleteNode($node->left, $value);
        } elseif ($value > $node->value) {
            $node->right = $this->deleteNode($node->right, $value);
        } else {
            // STEP 2: Node found

            // CASE 1: No child (leaf node)
            if ($node->left === null && $node->right === null) {
                return null;
            }

            // CASE 2: One child
            if ($node->left === null) {
                return $node->right;
            }
            if ($node->right === null) {
                return $node->left;
            }

            // CASE 3: Two children -> find the inorder successor = the lowest value node in the right subtree
            $successor = $this->findMin($node->right);
            $node->value = $successor->value;

            $node->right = $this->deleteNode($node->right, $successor->value);
        }

        return $node;
    }

    private function findMin($node)
    {
        while ($node->left !== null) {
            $node = $node->left;
        }
        return $node;
    }

    /* -------------------
      PREORDER: Root → Left → Right
   ------------------- */
    public function preorder($node) {
        if ($node == null) {
            return;
        }

        echo $node->value . " ";
        $this->preorder($node->left);
        $this->preorder($node->right);
    }

    public function preorderIterative($root)
    {
        $stack = [$root];

        while (!empty($stack)) {
            // Visit the node
            $currentNode = array_pop($stack);
            echo $currentNode->value . " ";

            // Push right first, then left so when we pop, we visit the left first
            if ($currentNode->right !== null) {
                $stack[] = $currentNode->right;
            }

            if ($currentNode->left !== null) {
                $stack[] = $currentNode->left;
            }
        }
    }

    /* -------------------
       INORDER: Left → Root → Right
       (Sorted output for BST)
    ------------------- */
    public function inorder($node)
    {
        if ($node == null) {
            return;
        }

        $this->inorder($node->left);
        echo $node->value . " ";
        $this->inorder($node->right);
    }

    public function inorderIterative($root)
    {
        $stack = []; // stack is empty because we do not know which is the deepest element yet
        $currentNode = $root;

        while ($currentNode !== null || !empty($stack)) {
            // Go to the leftmost node
            while ($currentNode !== null) {
                $stack[] = $currentNode;
                $currentNode = $currentNode->left;
            }

            // Visit the node
            $currentNode = array_pop($stack);
            echo $currentNode->value . " ";

            // Move to the right subtree
            $currentNode = $currentNode->right;
        }
    }

    /* -------------------
       POSTORDER: Left → Right → Root
    ------------------- */
    public function postorder($node)
    {
        if ($node == null) {
            return;
        }

        $this->postorder($node->left);
        $this->postorder($node->right);
        echo $node->value . " ";
    }

    public function postorderIterativeOneStack($root)
    {
        $stack = [];
        $lastVisited = null;
        $currentNode = $root;

        while ($currentNode !== null || !empty($stack)) {
            // 1. Go left as far as possible
            while ($currentNode !== null) {
                $stack[] = $currentNode;
                $currentNode = $currentNode->left;
            }

            // 2. Peek the top of the stack (do NOT pop yet)
            $peek = end($stack);

            // CASE A: No right child OR right child already visited → visit node
            if ($peek->right === null || $lastVisited === $peek->right) {
                // Visit the node
                $lastVisited = array_pop($stack);
                echo $peek->value . " ";
            } else {
                // CASE B: Right child exists AND we haven't visited it yet -> move to the right subtree and push the node to the stack
                $currentNode = $peek->right;
            }
        }
    }

    public function postorderIterativeTwoStacks($root)
    {
        $stack1 = [$root]; // stack1 is stack2 reversed (Root -> Right -> Left) : root is the first element in stack1 because we traverse root->right->left in stack1
        $stack2 = []; // we array_pop from this stack and get Left -> Right -> Root

        while (!empty($stack1)) {
            // Visit the node and push it to stack2 (final correct stack where we will pop elements in the right order)
            $currentNode = array_pop($stack1);
            $stack2[] = $currentNode;

            // Push left first and then right children to stack1 so when we pop, we visit the right first
            if ($currentNode->left !== null) {
                $stack1[] = $currentNode->left;
            }

            if ($currentNode->right !== null) {
                $stack1[] = $currentNode->right;
            }
        }

        while (!empty($stack2)) {
            $currentNode = array_pop($stack2);
            echo $currentNode->value . " ";
        }
    }

    /* -------------------
       LEVEL ORDER (BFS using Queue) -> visit each level from left to right and top to bottom
    ------------------- */
    public function levelOrder($root) {
        if ($root == null) {
            return;
        }

        $queue = [];
        $queue[] = $root;

        while (!empty($queue)) {
            // Visit the node
            $currentNode = array_shift($queue);
            echo $currentNode->value . " ";

            // Push left first and then right children to queue so when we shift , we visit the left first -> we push at the end of the queue (top)
            if ($currentNode->left != null) {
                $queue[] = $currentNode->left;
            }

            if ($currentNode->right != null) {
                $queue[] = $currentNode->right;
            }
        }
    }

    public function traversePreorder(): void
    {
        echo "Traversal preorder: ";
        $this->preorder($this->root);
        echo PHP_EOL;
    }

    public function traversePreorderIterative(): void
    {
        echo "Traversal preorder iterative: ";
        $this->preorderIterative($this->root);
        echo PHP_EOL;
    }

    public function traverseInorder(): void
    {
        echo "Traversal inorder: ";
        $this->inorder($this->root);
        echo PHP_EOL;
    }

    public function traverseInorderIterative(): void
    {
        echo "Traversal inorder iterative: ";
        $this->inorderIterative($this->root);
        echo PHP_EOL;
    }

    public function traversePostorder(): void
    {
        echo "Traversal postorder: ";
        $this->postorder($this->root);
        echo PHP_EOL;
    }

    public function traversePostorderIterativeOneStack(): void
    {
        echo "Traversal postorder iterative one stack: ";
        $this->postorderIterativeOneStack($this->root);
        echo PHP_EOL;
    }

    public function traversePostorderIterative(): void
    {
        echo "Traversal postorder iterative two stacks: ";
        $this->postorderIterativeTwoStacks($this->root);
        echo PHP_EOL;
    }

    public function traverseLevelOrder(): void
    {
        echo "Traversal level order: ";
        $this->levelOrder($this->root);
        echo PHP_EOL;
    }

    public function searchNodeValue()
    {
        echo "Search node value: ";
        $this->search($this->root, 12);
        echo PHP_EOL;
    }

    public function searchNodeValueIterative()
    {
        echo "Search node value iterative: ";
        $this->searchIterative($this->root, 12);
        echo PHP_EOL;
    }
}

// Example usage
$bst = new BinarySearchTree();

foreach ([6,2,13,4,5,1,7,12,9,10,11,8,14,3,15] as $value) {
    $bst->insert($value);
}

//$bst->delete(13);

$bst->traversePreorder();
$bst->traversePreorderIterative();
$bst->traverseInorder();
$bst->traverseInorderIterative();
$bst->traversePostorder();
$bst->traversePostorderIterative();
$bst->traversePostorderIterativeOneStack();
$bst->traverseLevelOrder();

$bst->searchNodeValue();
$bst->searchNodeValueIterative();



