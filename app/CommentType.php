<?php

namespace App;

enum CommentType: string
{
    case Text = 'text';
    case File = 'file';
}
