

My normal preference is to do Test-Driven-Development following some simple guidelines:

 - Don't repeat yourself.
      -- The same code should not be repeated all over the codebase.
 - Fat Model, Skinny Controller
      -- Use controllers for routing and decision making, put data manipulation into Models or some type of helper class.
 - Do Only One Thing.
      -- Try to keep methods small, and avoid over nesting of conditionals or loops.  It makes Unit testing easier, but 
         also makes your code easier to read and debug.
         
         
Unfortunately, there are many constraints that can impact my ability to write code that way.  Some common constraints are:

  -- framework constraints
  -- time constraints
  -- client requirements
  
  
  
In this case the client was using OpenCart (which I don't recommend), and needed a quick and dirty solution ASAP.  They 
needed a script that would scan all of the .svg files that had been uploaded to the server and find the part numbers 
(model numbers) embedded in the svg text, then save those part numbers to the database for later reference.

Upon finishing the script, I was informed that the requirements had been amended.  They now wanted code that would save 
the part numbers to the database when they upload the svg files to the server.   No problem.  Because I have a habit of
keeping my methods concise, I could simply modify the code in OpenCart that handles the svg uploads.  In my SVGReader
class, I changed one method from private to public and was able to use it as either a command line script or have it 
instantiated inside the OpenCart Model and it would work fine in either case without major modifications.

Because the svgs are uploaded by admins from a password protected area of the site, and the part numbers were pulled
from the xml text in svg images, SQL parameter binding was not a major concern for this assignment.


