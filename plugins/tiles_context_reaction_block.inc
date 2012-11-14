<?php

/**
 * Expose blocks with proper weights as context reactions.
 *
 * This context gets triggered after context_reaction_block, so weights of
 * blocks assigned to this context will take precedence. This is especially
 * important when ordering the system-main block, since it needs to live in two
 * locations (global context and tiles context for current page). Context
 * doesn't handle weighting blocks that are in multiple contexts very well. This
 * context will always weight it's blocks after context, so there will be proper
 * weights for ordering.
 */
class tiles_context_reaction_block extends context_reaction_block {}
