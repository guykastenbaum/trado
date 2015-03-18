# trado
*Automatically exported from code.google.com/p/trado*

**php utility to help translating a web site which was not meant to be translated.**

It works on two directories (source and target langages), can compare two html, creates a csv translate file for each file, edits these files, execute translation of partial phrases. It is a bit aware of what is text and what is code (html, tags, quotes) but not so smart : you have to be careful ....

It is meant to be installed on the target website.

Runs with php5, jquery, no database.

You have to make some files and directory writables.

The goal is to support diffusion of minor changes in html sources.
