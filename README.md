# CodeIgniter Image Magician

An ImageMagick library for CodeIgniter.

## Requirements
* CodeIgniter 2.0+
* ImageMagick

## Installation
Add the Image_magician.php file to your "libraries" directory in the application folder.

## Usage
Load the helper any time you want to use one of it's functions

```php
$this->load->library('image_magician');
```

You can then call any of the functions through the `$this->image_magician` object.

Example:

```php
$this->image_magician->is_animated($image_path);
```

## License
*(This project is released under the MIT license.)*

Copyright (c) 2012 Steven Benner, http://stevenbenner.com/

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.