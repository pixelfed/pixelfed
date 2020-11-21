# Contributing

## Bug Reports
To encourage active collaboration, Pixelfed strongly encourages pull requests, not just bug reports. "Bug reports" may also be sent in the form of a pull request containing a failing test.
    
However, if you file a bug report, your issue should contain a title and a clear description of the issue. You should also include as much relevant information as possible and a code sample that demonstrates the issue. The goal of a bug report is to make it easy for yourself - and others - to replicate the bug and develop a fix.
    
Remember, bug reports are created in the hope that others with the same problem will be able to collaborate with you on solving it. Do not expect that the bug report will automatically see any activity or that others will jump to fix it. Creating a bug report serves to help yourself and others start on the path of fixing the problem.

## Core Development Discussion
Informal discussion regarding bugs, new features, and implementation of existing features takes place in the ```#pixelfed-dev``` channel on the Freenode IRC network.

## Branches
If you want to contribute to this repository, please file your pull request against the `staging` branch. 

Pixelfed Beta currently uses the `dev` branch for deployable code. When v1.0 is released, the stable branch will be changed to `master`, with `dev` branch being used for development and testing.

## Compiled Assets
If you are submitting a change that will affect a compiled file, such as most of the files in ```resources/assets/sass``` or ```resources/assets/js``` of the pixelfed/pixelfed repository, do not commit the compiled files. Due to their large size, they cannot realistically be reviewed by a maintainer. This could be exploited as a way to inject malicious code into Pixelfed. In order to defensively prevent this, all compiled files will be generated and committed by Pixelfed maintainers.

## Security Vulnerabilities
If you discover a security vulnerability within Pixelfed, please send an email to Daniel Supernault at hello@pixelfed.org. All security vulnerabilities will be promptly addressed.
